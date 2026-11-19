<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Filament\Resources\TenderSubmissionResource;
use App\Models\TenderSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Mailed to the admin inbox (TenderNotificationRecipients) when a user sends a
 * tender through «Tender yarat». Admin-facing, so the copy is Azerbaijani like the
 * panel; the applicant's address is the reply-to.
 */
final class TenderSubmissionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly TenderSubmission $submission) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $s = $this->submission->loadMissing('category');

        $message = (new MailMessage)
            ->subject('Yeni tender müraciəti: '.$s->title)
            ->replyTo($s->email, $s->contact_name)
            ->greeting('Yeni tender müraciəti')
            ->line('İstifadəçi saytda «Tender yarat» forması ilə tender göndərib.')
            ->line('Tender: '.$s->title)
            ->line('Kateqoriya: '.($s->category?->getTranslation('name', 'az') ?? '—'))
            ->line('Şəhər / ünvan: '.$s->city);

        if ($s->area_sqm !== null) {
            $message->line('Sahə: '.rtrim(rtrim(number_format((float) $s->area_sqm, 2, '.', ' '), '0'), '.').' m²');
        }
        if ($budget = $s->budgetLabel()) {
            $message->line('Təxmini büdcə: '.$budget);
        }
        if ($s->deadline_at) {
            $message->line('Təkliflərin son tarixi: '.$s->deadline_at->format('d.m.Y'));
        }

        $message->line('İşin təsviri: '.str($s->description)->limit(1500));

        if (filled($s->organizer_name)) {
            $message->line('Sifarişçi / şirkət: '.$s->organizer_name);
        }

        return $message
            ->line('Əlaqə şəxsi: '.$s->contact_name)
            ->line('Telefon: '.$s->phone)
            ->line('Email: '.$s->email)
            ->action('Müraciətə bax', TenderSubmissionResource::getUrl('edit', ['record' => $s]));
    }
}
