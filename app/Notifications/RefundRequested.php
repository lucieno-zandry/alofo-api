<?php
// app/Notifications/RefundRequested.php

namespace App\Notifications;

use App\Helpers\Functions;
use App\Models\RefundRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class RefundRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RefundRequest $refundRequest,
        public Transaction $transaction,
        public User $customer
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = Functions::get_frontend_url('REFUND_REQUESTS', 'ADMIN') . $this->refundRequest->uuid;

        return (new MailMessage)
            ->subject('New Refund Request #' . $this->refundRequest->uuid)
            ->greeting('Hello Admin,')
            ->line('A new refund request has been submitted.')
            ->line('Customer: ' . $this->customer->name . ' (' . $this->customer->email . ')')
            ->line('Transaction UUID: ' . $this->transaction->uuid)
            ->line('Amount: ' . Functions::format_money($this->refundRequest->amount))
            ->line('Reason: ' . $this->refundRequest->reason)
            ->action('Review Request', $url);
    }

    public function toArray($notifiable): array
    {
        return [
            'notification_type' => 'refund_request',
            'type'              => 'refund_requested',
            'refund_request_uuid' => $this->refundRequest->uuid,
            'transaction_uuid'    => $this->transaction->uuid,
            'customer_name'       => $this->customer->name,
            'amount'              => $this->refundRequest->amount,
            'reason'              => $this->refundRequest->reason,
            'message'             => 'New refund request from ' . $this->customer->name,
        ];
    }
}
