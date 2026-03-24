<?php

namespace App\Notifications\Customer;

use App\Helpers\Functions;
use App\Models\ClientCode;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ClientCodeAttached extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public ClientCode $clientCode,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $line = "You now have access to exclusive promotions with client code '{$this->clientCode->code}'! Enjoy special discounts on eligible products.";
        $url = Functions::get_frontend_url('PRODUCTS_PAGE_PATHNAME');

        return (new MailMessage)
            ->subject('You have received a client code!')
            ->line($line)
            ->action('Shop Now', $url)
            ->line('Thank you for being our valued customer!');
    }

    public function toArray($notifiable): array
    {
        return [
            'notification_type' => 'client_code',
            'action' => 'attach',
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'client_code' => $this->clientCode->code,
            'message' => "You now have access to exclusive promotions with code '{$this->clientCode->code}'.",
        ];
    }
}
