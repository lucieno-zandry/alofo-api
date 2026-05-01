<?php

namespace App\Notifications;

use App\Helpers\Functions;
use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class RefundRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RefundRequest $refundRequest
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Refund Request Update')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your refund request has been reviewed and unfortunately could not be approved.')
            ->line('Transaction: ' . $this->refundRequest->transaction_uuid)
            ->line('Requested Amount: ' . Functions::format_money($this->refundRequest->amount))
            ->line('Reason provided: ' . $this->refundRequest->reason)
            ->line('Admin notes: ' . ($this->refundRequest->admin_notes ?? 'No additional notes.'))
            ->line('If you have any questions, please contact our support team.');
    }

    public function toArray($notifiable): array
    {
        return [
            'notification_type'   => 'refund',
            'type'                => 'refund_rejected',
            'refund_request_uuid' => $this->refundRequest->uuid,
            'transaction_uuid'    => $this->refundRequest->transaction_uuid,
            'amount'              => $this->refundRequest->amount,
            'admin_notes'         => $this->refundRequest->admin_notes,
            'message'             => 'Your refund request was not approved.',
        ];
    }
}
