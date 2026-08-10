<?php

declare(strict_types=1);

namespace Tests;

use Database\Seeders\TranslationsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    /**
     * UI copy lives in the `translations` table, so every view that calls t() needs it
     * present. Without this the whole suite asserts against raw translation keys.
     */
    protected bool $seed = true;

    protected string $seeder = TranslationsSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        $compiledViewPath = storage_path('framework/testing-views');
        File::ensureDirectoryExists($compiledViewPath);
        config()->set('view.compiled', $compiledViewPath);
    }
}
