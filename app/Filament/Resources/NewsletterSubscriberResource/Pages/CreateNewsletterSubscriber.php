<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsletterSubscriberResource\Pages;

use App\Filament\Resources\NewsletterSubscriberResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNewsletterSubscriber extends CreateRecord
{
    protected static string $resource = NewsletterSubscriberResource::class;

    /** Store the address the same way the public form does. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['email'] = mb_strtolower(trim((string) $data['email']));

        return $data;
    }
}
