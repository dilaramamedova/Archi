<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $compiledViewPath = storage_path('framework/testing-views');
        File::ensureDirectoryExists($compiledViewPath);
        config()->set('view.compiled', $compiledViewPath);
    }
}
