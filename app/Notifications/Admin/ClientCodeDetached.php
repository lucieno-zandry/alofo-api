<?php

namespace App\Notifications\Admin;

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
        public ?User $performedBy = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $line = "Client code '{$this->clientCode->code}' was detached from user {$this->user->name}.";
        $url = Functions::get_frontend_url('USER_DETAIL_PAGE_PATHNAME', 'ADMIN') . $this->user->id;

        if ($this->performedBy) {
            $line .= " Action performed by {$this->performedBy->name}.";
        }

        return (new MailMessage)
            ->subject('Client Code Detached (Admin)')
            ->line($line)
            ->action('View User', $url)
            ->line('Thank you for using our application!');
    }

    public function toArray($notifiable): array
    {
        return [
            'notification_type' => 'client_code',
            'action' => 'detach',
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'client_code' => $this->clientCode->code,
            'performed_by' => $this->performedBy?->id,
            'performed_by_name' => $this->performedBy?->name,
            'message' => "Client code '{$this->clientCode->code}' was detached from user {$this->user->name}.",
        ];
    }
}
