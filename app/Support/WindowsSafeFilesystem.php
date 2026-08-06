<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

final class WindowsSafeFilesystem extends Filesystem
{
    public function replace($path, $content, $mode = null): void
    {
        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;
        $temporaryPath = tempnam(dirname($path), basename($path));

        if ($temporaryPath === false) {
            throw new RuntimeException("Unable to create a temporary file for [{$path}].");
        }

        if ($mode !== null) {
            @chmod($temporaryPath, $mode);
        } else {
            @chmod($temporaryPath, 0777 - umask());
        }

        file_put_contents($temporaryPath, $content, LOCK_EX);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            if (@rename($temporaryPath, $path)) {
                return;
            }

            usleep(20_000 * ($attempt + 1));
            clearstatcache(true, $path);
        }

        // Windows can keep the destination file locked briefly while another Herd
        // worker reads it. Writing in-place under an exclusive lock avoids a 500.
        if (file_put_contents($path, $content, LOCK_EX) === false) {
            @unlink($temporaryPath);

            throw new RuntimeException("Unable to replace the file [{$path}].");
        }

        @unlink($temporaryPath);
    }
}
