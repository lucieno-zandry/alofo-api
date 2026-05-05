<?php
// app/Notifications/DisputeResolved.php

namespace App\Notifications;

use App\Helpers\Functions;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class DisputeResolved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Transaction $transaction,
        public string $outcome,
        public string $reason
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $orderUrl = Functions::get_frontend_url('customer_order_details_pathname') . $this->transaction->order_uuid;

        return (new MailMessage)
            ->subject('Dispute Resolution Update')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('The dispute on your transaction has been resolved.')
            ->line('Transaction: ' . $this->transaction->uuid)
            ->line('Outcome: ' . $this->outcome)
            ->line('Admin explanation: ' . $this->reason)
            ->action('View Order', $orderUrl);
    }

    public function toArray($notifiable): array
    {
        return [
            'notification_type' => 'dispute',
            'type'              => 'dispute_resolved',
            'transaction_uuid'  => $this->transaction->uuid,
            'order_uuid'        => $this->transaction->order_uuid,
            'outcome'           => $this->outcome,
            'reason'            => $this->reason,
            'message'           => 'Your dispute has been resolved. Outcome: ' . $this->outcome,
        ];
    }
}
