<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Filament\Resources\TenderAccessRequestResource;
use App\Models\TenderAccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Mailed to the admin inbox (TenderNotificationRecipients) when a user asks to
 * join the tender section. Admin-facing, so the copy is Azerbaijani like the
 * panel; the applicant's address is the reply-to so the admin can answer at once.
 */
final class TenderAccessRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly TenderAccessRequest $accessRequest) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->accessRequest->loadMissing('package');
        $package = $request->package;

        $message = (new MailMessage)
            ->subject('Tenderlərə qoşulma müraciəti: '.$request->full_name)
            ->replyTo($request->email, $request->full_name)
            ->greeting('Yeni qoşulma müraciəti')
            ->line('İstifadəçi tender bölməsinə giriş üçün müraciət edib.')
            ->line('Ad və soyad: '.$request->full_name);

        if (filled($request->company_name)) {
            $message->line('Şirkət: '.$request->company_name);
        }

        $message->line('Telefon: '.$request->phone)
            ->line('Email: '.$request->email);

        if ($package) {
            $price = $package->priceLabel();
            $message->line('Paket: '.$package->getTranslation('name', 'az').($price ? ' — '.$price : ''));
        }

        if (filled($request->message)) {
            $message->line('Mesaj: '.$request->message);
        }

        return $message->action(
            'Müraciətə bax və girişi aktivləşdir',
            TenderAccessRequestResource::getUrl('edit', ['record' => $request]),
        );
    }
}
