<?php

namespace App\Notifications;

use App\Models\Transaction;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentSuccess extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Transaction $transaction,
        public Order $order,
        public string $order_detail_url,
    ) {}

    /**
     * Get the notification's delivery channels
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
            ->subject('Payment Successful - Order #' . $this->order->uuid)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your payment of ' . number_format($this->transaction->amount, 2) . ' has been successfully processed.')
            ->line('Order Number: ' . $this->order->uuid)
            ->line('Payment Method: ' . $this->transaction->method)
            ->line('Total Amount: ' . number_format($this->order->total, 2))
            ->action('View Order Details', url($this->order_detail_url . $this->order->uuid))
            ->line('Thank you for your purchase!');
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        return [
            "notification_type" => "transaction",
            'type' => 'payment_success',
            'transaction_id' => $this->transaction->id,
            'order_uuid' => $this->order->uuid,
            'amount' => $this->transaction->amount,
            'payment_method' => $this->transaction->method,
            'message' => 'Your payment of ' . number_format($this->transaction->amount, 2) . ' was successful.',
            'order_total' => $this->order->total,
        ];
    }
}
