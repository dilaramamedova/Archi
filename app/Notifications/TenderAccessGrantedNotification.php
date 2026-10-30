<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TenderSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells the user the admin opened the tender section for them, and until when. */
final class TenderAccessGrantedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly TenderSubscription $subscription) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $until = $this->subscription->ends_at->timezone('Asia/Baku')->format('d.m.Y');

        return (new MailMessage)
            ->subject(t('notifications.tender_access.subject'))
            ->greeting(t('notifications.greeting', ['name' => $notifiable->first_name ?? $notifiable->name]))
            ->line(t('notifications.tender_access.line', ['date' => $until]))
            ->action(t('notifications.tender_access.action'), route('tenders.index'))
            ->line(t('notifications.signature'));
    }
}
