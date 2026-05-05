<?php

namespace App\Notifications\Admin;

use App\Helpers\Functions;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CollaboratorWaitingApproval extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $collaborator) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $user = $this->collaborator;

        return (new MailMessage)
            ->subject('⏳ New Collaborator Awaiting Approval - ' . $user->name)
            ->greeting('Hello Admin,')
            ->line('A new collaborator has registered and is waiting for your approval.')
            ->line('**Name:** ' . $user->name)
            ->line('**Email:** ' . $user->email)
            ->line('**Role:** ' . ucfirst($user->role))
            ->line('**Registered at:** ' . $user->created_at->format('Y-m-d H:i:s'))
            ->action('Review Collaborator', Functions::get_user_detail_page_url($user->id))
            ->line('Please review and approve or reject this collaborator.');
    }

    public function toArray(object $notifiable): array
    {
        $user = $this->collaborator;

        return [
            'notification_type' => 'user',
            'type' => 'user_waiting_approval',
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'user_role' => $user->role,
            'registered_at' => $user->created_at->toISOString(),
            'message' => "Collaborator {$user->name} ({$user->email}) is waiting for approval.",
        ];
    }
}
