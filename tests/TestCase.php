<?php

declare(strict_types=1);

namespace Tests;

use Database\Seeders\TranslationsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
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

        // RefreshDatabase rolls the database back between tests; nothing rolls the
        // cache back. The store is `array` here, so it lives as long as the PHP
        // process — and VersionedCache keeps whole payloads in it (the homepage
        // blocks, the classifier tree, the header chrome, the calculator config).
        // A payload cached by one test was therefore served to the next one, whose
        // database no longer holds the rows behind it: the models came back detached
        // from their relations and the failure surfaced somewhere else entirely, as a
        // QueryException or a missing name, in a file that passed on its own. Every
        // test starts cold instead.
        Cache::flush();

        $compiledViewPath = storage_path('framework/testing-views');
        File::ensureDirectoryExists($compiledViewPath);
        config()->set('view.compiled', $compiledViewPath);
    }
}
