<?php

namespace App\Notifications;

use App\Helpers\Functions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $status;
    protected string $message;
    protected ?string $reason;

    public function __construct(string $status, string $message, ?string $reason = null)
    {
        $this->status = $status;
        $this->message = $message;
        $this->reason = $reason;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $subject = 'Account Status Update';
        $greeting = "Hello {$notifiable->name},";
        $url = Functions::get_frontend_url('account_settings_pathname');

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($this->message)
            ->when($this->reason, fn($mail) => $mail->line("Reason: {$this->reason}"))
            ->line('If you have any questions, please contact support.')
            ->action('View Your Account', $url)
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification (database).
     */
    public function toDatabase($notifiable): array
    {
        return [
            'notification_type' => 'system',   // or a custom type like 'user_status'
            'title' => 'Account Status Update',
            'message' => $this->message,
            'status' => $this->status,
            'reason' => $this->reason,
        ];
    }
}
