<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ?string $reason = null) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(t('notifications.account_rejected.subject'))
            ->greeting(t('notifications.greeting', ['name' => $notifiable->first_name ?? $notifiable->name]))
            ->line(t('notifications.account_rejected.line'));

        if (filled($this->reason)) {
            $message->line(t('notifications.account_rejected.reason', ['reason' => $this->reason]));
        }

        return $message->line(t('notifications.signature'));
    }
}
