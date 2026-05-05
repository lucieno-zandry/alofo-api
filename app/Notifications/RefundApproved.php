<?php
// app/Notifications/RefundApproved.php

namespace App\Notifications;

use App\Helpers\Functions;
use App\Models\RefundRequest;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class RefundApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RefundRequest $refundRequest,
        public Transaction $refundTransaction
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $orderUrl = Functions::get_frontend_url('customer_order_details_pathname') . $this->refundTransaction->order_uuid;

        return (new MailMessage)
            ->subject('Refund Request Approved')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your refund request has been approved.')
            ->line('Original Transaction: ' . $this->refundRequest->transaction_uuid)
            ->line('Refund Amount: ' . Functions::format_money($this->refundTransaction->amount))
            ->line('Reason: ' . $this->refundRequest->reason)
            ->line('The refund is being processed and should appear in your account within 5-7 business days.')
            ->action('View Order', $orderUrl);
    }

    public function toArray($notifiable): array
    {
        return [
            'notification_type'   => 'refund',
            'type'                => 'refund_approved',
            'refund_request_uuid' => $this->refundRequest->uuid,
            'refund_transaction_uuid' => $this->refundTransaction->uuid,
            'order_uuid'          => $this->refundTransaction->order_uuid,
            'amount'              => $this->refundTransaction->amount,
            'message'             => 'Your refund request has been approved.',
        ];
    }
}
