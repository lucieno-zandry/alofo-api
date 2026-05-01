<?php

namespace App\Notifications\Admin;

use App\Enums\UserRole;
use App\Helpers\Functions;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\SettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransactionSuccessAdmin extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Transaction $transaction,
        public Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database']; // mail + stored notification
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('✅ Payment Received - Order #' . $this->order->uuid)
            ->greeting('Hello Admin,')
            ->line('A payment has been successfully completed.')
            ->line('**Transaction ID:** ' . $this->transaction->uuid)
            ->line('**Order ID:** ' . $this->order->uuid)
            ->line('**Amount:** ' . Functions::format_money($this->transaction->amount))
            ->line('**Payment Method:** ' . $this->transaction->method)
            ->line('**Order Total:** ' . Functions::format_money($this->order->total))
            ->action('View Order', Functions::get_frontend_url('ADMIN_ORDER_DETAILS_PATHNAME', UserRole::ADMIN->value) . $this->order->uuid)
            ->line('Please proceed with fulfillment if not already processed.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            "notification_type" => "transaction",
            "type" => "payment_success",

            "transaction_uuid" => $this->transaction->uuid,
            "order_uuid" => $this->order->uuid,

            "amount" => $this->transaction->amount,
            "payment_method" => $this->transaction->method,

            "order_total" => $this->order->total,
            "user_id" => $this->order->user_id,

            "message" => 'Payment of ' . Functions::format_money($this->transaction->amount) . ' received for order #' . $this->order->uuid . '.',
        ];
    }
}
