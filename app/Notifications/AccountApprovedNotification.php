<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(t('notifications.account_approved.subject'))
            ->greeting(t('notifications.greeting', ['name' => $notifiable->first_name ?? $notifiable->name]))
            ->line(t('notifications.account_approved.line'))
            ->action(t('notifications.account_approved.action'), route('login'))
            ->line(t('notifications.signature'));
    }
}
