<?php

namespace App\Notifications\Admin;

use App\Helpers\Functions;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $user = $this->user;

        return (new MailMessage)
            ->subject('🎉 New Customer Registration - ' . $user->name)
            ->greeting('Hello Admin,')
            ->line('A new customer has successfully registered on our platform.')
            ->line('**Name:** ' . $user->name)
            ->line('**Email:** ' . $user->email)
            ->line('**Role:** ' . ucfirst($user->role))
            ->line('**Email verified:** ' . ($user->email_verified_at ? 'Yes' : 'No'))
            ->line('**Registered at:** ' . $user->created_at->format('Y-m-d H:i:s'))
            ->action('View User Profile', Functions::get_user_detail_page_url($user->id))
            ->line('You can manage this user from the admin panel.');
    }

    public function toArray(object $notifiable): array
    {
        $user = $this->user;

        return [
            'notification_type' => 'user',
            'type' => 'user_registration',
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'user_role' => $user->role,
            'email_verified' => !is_null($user->email_verified_at),
            'registered_at' => $user->created_at->toISOString(),
            'message' => "New user {$user->name} ({$user->email}) has registered.",
        ];
    }
}
