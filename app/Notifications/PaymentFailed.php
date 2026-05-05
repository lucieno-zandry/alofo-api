<?php

namespace App\Notifications;

use App\Helpers\Functions;
use App\Models\Transaction;
use App\Models\Order;
use App\Services\SettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Transaction $transaction,
        public Order $order,
        public $order_details_url,
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment Failed - Order #' . $this->order->uuid)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Unfortunately, your payment of ' . Functions::format_money($this->transaction->amount) . ' could not be processed.')
            ->line('Order Number: ' . $this->order->uuid)
            ->line('Payment Method: ' . $this->transaction->method)
            ->line('Amount: ' . Functions::format_money($this->order->total))
            ->line('Please try again or use a different payment method.')
            ->action('Retry Payment', $this->order_details_url . $this->order->uuid)
            ->line('If you continue to experience issues, please contact our support team.');
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        return [
            "notification_type" => "transaction",
            'type' => 'payment_failed',
            'transaction_uuid' => $this->transaction->uuid,
            'order_uuid' => $this->order->uuid,
            'amount' => $this->transaction->amount,
            'payment_method' => $this->transaction->method,
            'message' => 'Payment of ' . Functions::format_money($this->transaction->amount) . ' failed. Please try again.',
            'order_total' => $this->order->total,
        ];
    }
}
