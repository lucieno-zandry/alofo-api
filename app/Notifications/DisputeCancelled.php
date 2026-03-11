<?php

namespace App\Notifications;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class DisputeCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Transaction $transaction,
        public User $customer
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Dispute Cancelled')
            ->greeting('Hello Admin,')
            ->line('A customer has cancelled their dispute.')
            ->line('Customer: ' . $this->customer->name . ' (' . $this->customer->email . ')')
            ->line('Transaction UUID: ' . $this->transaction->uuid)
            ->line('Order UUID: ' . $this->transaction->order_uuid);
    }

    public function toArray($notifiable): array
    {
        return [
            'notification_type' => 'dispute',
            'type'              => 'dispute_cancelled',
            'transaction_uuid'  => $this->transaction->uuid,
            'order_uuid'        => $this->transaction->order_uuid,
            'customer_name'     => $this->customer->name,
            'message'           => 'Dispute cancelled by customer',
        ];
    }
}
