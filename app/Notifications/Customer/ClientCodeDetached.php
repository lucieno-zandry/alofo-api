<?php

namespace App\Notifications\Customer;

use App\Helpers\Functions;
use App\Models\ClientCode;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ClientCodeDetached extends Notification implements ShouldQueue
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
        $line = "Your client code '{$this->clientCode->code}' has been removed. You will no longer receive exclusive promotions associated with this code.";
        $url = Functions::get_frontend_url('CONTACT_PAGE_PATHNAME', 'USER'); // e.g., contact page for support

        return (new MailMessage)
            ->subject('Client Code Removed')
            ->line($line)
            ->action('Contact Support', $url)
            ->line('If you have questions, please contact our support team.');
    }

    public function toArray($notifiable): array
    {
        return [
            'notification_type' => 'client_code',
            'action' => 'detach',
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'client_code' => $this->clientCode->code,
            'message' => "Your client code '{$this->clientCode->code}' has been removed.",
        ];
    }
}
