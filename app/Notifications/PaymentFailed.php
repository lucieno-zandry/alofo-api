<?php

namespace App\Notifications;

use App\Models\Transaction;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentFailed extends Notification
{
    use Queueable;

    public function __construct(
        public Transaction $transaction,
        public Order $order
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
            ->line('Unfortunately, your payment of ' . number_format($this->transaction->amount, 2) . ' could not be processed.')
            ->line('Order Number: ' . $this->order->uuid)
            ->line('Payment Method: ' . $this->transaction->method)
            ->line('Amount: ' . number_format($this->order->total, 2))
            ->line('Please try again or use a different payment method.')
            ->action('Retry Payment', env("ORDER_DETAILS_URL") . $this->order->uuid)
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
            'transaction_id' => $this->transaction->id,
            'order_uuid' => $this->order->uuid,
            'amount' => $this->transaction->amount,
            'payment_method' => $this->transaction->method,
            'message' => 'Payment of ' . number_format($this->transaction->amount, 2) . ' failed. Please try again.',
            'order_total' => $this->order->total,
        ];
    }
}
