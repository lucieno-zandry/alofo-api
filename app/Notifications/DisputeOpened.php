<?php
// app/Notifications/DisputeOpened.php

namespace App\Notifications;

use App\Helpers\Functions;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class DisputeOpened extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Transaction $transaction,
        public User $customer,
        public string $reason
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = Functions::get_frontend_url('TRANSACTION_DETAILS', 'ADMIN') . $this->transaction->uuid;

        return (new MailMessage)
            ->subject('New Dispute Opened')
            ->greeting('Hello Admin,')
            ->line('A customer has opened a dispute.')
            ->line('Customer: ' . $this->customer->name . ' (' . $this->customer->email . ')')
            ->line('Transaction UUID: ' . $this->transaction->uuid)
            ->line('Order UUID: ' . $this->transaction->order_uuid)
            ->line('Amount: ' . number_format($this->transaction->amount, 2))
            ->line('Dispute reason: ' . $this->reason)
            ->action('View Transaction', $url);
    }

    public function toArray($notifiable): array
    {
        return [
            'notification_type' => 'dispute',
            'type'              => 'dispute_opened',
            'transaction_uuid'  => $this->transaction->uuid,
            'order_uuid'        => $this->transaction->order_uuid,
            'customer_name'     => $this->customer->name,
            'reason'            => $this->reason,
            'amount'            => $this->transaction->amount,
            'message'           => 'New dispute opened by ' . $this->customer->name,
        ];
    }
}
