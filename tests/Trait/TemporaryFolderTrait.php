<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Trait;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function chmod;
use function file_put_contents;
use function is_dir;
use function json_encode;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const JSON_THROW_ON_ERROR;

/**
 * A folder of its own for each test, removed afterwards.
 */
trait TemporaryFolderTrait
{
    private string $folder;

    protected function setUpFolder(): void
    {
        $this->folder = sys_get_temp_dir() . '/extension-api-' . uniqid();
        mkdir("{$this->folder}/vendor/composer", recursive: true);
    }

    protected function tearDownFolder(): void
    {
        if (! is_dir($this->folder)) {
            return;
        }

        chmod($this->folder, permissions: 0o755);
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->folder, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($files as $file) {
            if ($file instanceof SplFileInfo && $file->isDir()) {
                rmdir($file->getPathname());
                continue;
            }

            if ($file instanceof SplFileInfo) {
                unlink($file->getPathname());
            }
        }

        rmdir($this->folder);
    }

    /**
     * @param array<array-key, mixed> $data
     */
    protected function writeJson(string $path, array $data): void
    {
        file_put_contents("{$this->folder}/{$path}", json_encode($data, JSON_THROW_ON_ERROR));
    }
}
