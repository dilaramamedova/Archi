<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenderAccessRequestResource\Pages;

use App\Filament\Resources\TenderAccessRequestResource;
use App\Models\Setting;
use App\Support\TenderNotificationRecipients;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

final class ListTenderAccessRequests extends ListRecords
{
    protected static string $resource = TenderAccessRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('notificationEmail')
                ->label('Bildiriş emaili')
                ->icon('heroicon-o-envelope')
                ->color('gray')
                ->fillForm(fn (): array => ['emails' => (string) Setting::getRaw(TenderNotificationRecipients::SETTING, '')])
                ->schema([
                    Forms\Components\TextInput::make('emails')
                        ->label('Qoşulma müraciətləri bu ünvan(lar)a göndərilir')
                        ->helperText(fn (): string => 'Bir neçə ünvanı vergüllə ayırın. Hazırda: '
                            .(implode(', ', TenderNotificationRecipients::all()) ?: 'heç kimə göndərilmir'))
                        ->rule(fn () => function (string $attribute, $value, \Closure $fail): void {
                            $parts = array_filter(preg_split('/[\s,;]+/', (string) $value) ?: [], 'strlen');
                            $invalid = array_filter($parts, fn (string $part): bool => filter_var($part, FILTER_VALIDATE_EMAIL) === false);
                            if ($invalid !== []) {
                                $fail('Ünvanlardan biri düzgün email deyil.');
                            }
                        }),
                ])
                ->action(function (array $data): void {
                    Setting::set(TenderNotificationRecipients::SETTING, implode(', ', TenderNotificationRecipients::parse((string) ($data['emails'] ?? ''))));
                    Notification::make()->title('Bildiriş emaili yadda saxlanıldı')->success()->send();
                }),
        ];
    }
}
