<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Whether product forms ask for class-specific fields (class attributes, "where
 * to use" tags, the laminate card). See config/catalog.php.
 */
final class ClassifierFields
{
    public static function enabled(): bool
    {
        return (bool) config('catalog.classifier_fields');
    }
}
