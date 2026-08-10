<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Replaces Laravel's built-in reset notification so the copy comes from the
 * database-backed translations instead of the framework lang files.
 */
class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $token) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // The GET /reset-password route takes no path parameters, so both values
        // land in the query string — which is what the blade reads.
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject(t('notifications.reset_password.subject'))
            ->greeting(t('notifications.greeting', ['name' => $notifiable->first_name ?? $notifiable->name]))
            ->line(t('notifications.reset_password.line'))
            ->action(t('notifications.reset_password.action'), $url)
            ->line(t('notifications.reset_password.expire', [
                'count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ]))
            ->line(t('notifications.reset_password.ignore'));
    }
}
