<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\WindowsSafeFilesystem;
use Tests\TestCase;

class WindowsSafeFilesystemTest extends TestCase
{
    public function test_application_uses_safe_filesystem_on_windows(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->markTestSkipped('Windows-specific filesystem binding.');
        }

        $this->assertInstanceOf(WindowsSafeFilesystem::class, app('files'));
    }

    public function test_it_replaces_an_existing_compiled_file(): void
    {
        $directory = storage_path('framework/testing-filesystem');
        $path = $directory.DIRECTORY_SEPARATOR.'compiled.php';
        $filesystem = new WindowsSafeFilesystem;

        $filesystem->ensureDirectoryExists($directory);
        $filesystem->put($path, 'old content');
        $filesystem->replace($path, 'new content');

        $this->assertSame('new content', $filesystem->get($path));

        $filesystem->deleteDirectory($directory);
    }
}
