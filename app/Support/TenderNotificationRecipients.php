<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;

/**
 * Where tender join requests are mailed, first match wins:
 *   1. the "Bildiriş emaili" set on the admin's Tender müraciətləri page
 *      (setting tender_notification_email, comma-separated),
 *   2. MAIL_ADMIN_ADDRESS from the environment,
 *   3. every admin account.
 */
final class TenderNotificationRecipients
{
    public const SETTING = 'tender_notification_email';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $configured = self::parse((string) Setting::getRaw(self::SETTING, ''));

        if ($configured !== []) {
            return $configured;
        }

        $env = self::parse((string) config('mail.admin_address', ''));

        if ($env !== []) {
            return $env;
        }

        return User::query()
            ->withRole(UserRole::Admin)
            ->whereNotNull('email')
            ->pluck('email')
            ->filter(fn ($email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function parse(string $value): array
    {
        return collect(preg_split('/[\s,;]+/', $value) ?: [])
            ->map(fn (string $email): string => trim($email))
            ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();
    }
}
