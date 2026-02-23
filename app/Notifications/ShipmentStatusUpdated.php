<?php

namespace App\Notifications;

use App\Models\Shipment;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ShipmentStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Shipment $shipment,
        public Order $order,
        public string $order_detail_url,
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
        $mailMessage = (new MailMessage)
            ->subject($this->getSubject())
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->getStatusMessage())
            ->line('Order Number: ' . $this->order->uuid);

        // Add status-specific details
        if ($this->shipment->status === 'SHIPPED') {
            $data = $this->shipment->data;

            if (!empty($data['carrier'])) {
                $mailMessage->line('Carrier: ' . $data['carrier']);
            }

            if (!empty($data['tracking_number'])) {
                $mailMessage->line('Tracking Number: ' . $data['tracking_number']);
            }

            if (!empty($data['estimated_delivery'])) {
                $mailMessage->line('Estimated Delivery: ' . date('F j, Y', strtotime($data['estimated_delivery'])));
            }
        }

        if ($this->shipment->status === 'DELIVERED') {
            $mailMessage->line('Delivered on: ' . $this->shipment->updated_at->format('F j, Y'));
        }

        return $mailMessage
            ->action('View Order Details', url($this->order_detail_url . $this->order->uuid))
            ->line('Thank you for your order!');
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        return [
            "notification_type" => "shipment",
            'type' => 'shipment_status_updated',
            'shipment_id' => $this->shipment->id,
            'order_uuid' => $this->order->uuid,
            'status' => $this->shipment->status,
            'message' => $this->getStatusMessage(),
            'shipment_data' => $this->shipment->data,
        ];
    }

    /**
     * Get the email subject based on shipment status.
     */
    private function getSubject(): string
    {
        return match ($this->shipment->status) {
            'PROCESSING' => 'Order Processing - Order #' . $this->order->uuid,
            'SHIPPED' => 'Order Shipped - Order #' . $this->order->uuid,
            'DELIVERED' => 'Order Delivered - Order #' . $this->order->uuid,
            default => 'Shipment Update - Order #' . $this->order->uuid,
        };
    }

    /**
     * Get the status message based on shipment status.
     */
    private function getStatusMessage(): string
    {
        return match ($this->shipment->status) {
            'PROCESSING' => 'Your order is being prepared for shipment.',
            'SHIPPED' => 'Great news! Your order has been shipped and is on its way to you.',
            'DELIVERED' => 'Your order has been successfully delivered. We hope you enjoy your purchase!',
            default => 'Your shipment status has been updated.',
        };
    }
}
