<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Handler;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Handler\Download;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class DownloadTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string}>
     */
    public static function invalidProvider(): array
    {
        return [
            'an empty name'           => ['', 'text/csv', 'A download name must not be empty'],
            'an empty media type'     => ['entries.csv', '', 'A download media type must not be empty'],
            'a quote in the name'     => ['en"tries.csv', 'text/csv', 'must not hold quotes'],
            'a backslash in the name' => ['en\\tries.csv', 'text/csv', 'must not hold quotes'],
            'a slash in the name'     => ['../entries.csv', 'text/csv', 'must not hold quotes'],
            'a new line in the name'  => ["entries.csv\r\nSet-Cookie: a=b", 'text/csv', 'must not hold quotes'],
            'a nul in the name'       => ["entries\x00.csv", 'text/csv', 'must not hold quotes'],
            'a delete in the name'    => ["entries\x7f.csv", 'text/csv', 'must not hold quotes'],
        ];
    }

    #[Test]
    public function keepsANameWithSpacesAndAccents(): void
    {
        $download = new Download('Entrées – Contact 2026.csv', 'text/csv', static function ($stream): void {});

        static::assertSame(['Entrées – Contact 2026.csv', 'text/csv'], [$download->filename, $download->mediaType]);
    }

    #[Test]
    #[DataProvider('invalidProvider')]
    public function refusesANameOrTypeThatCouldBreakAHeader(string $filename, string $mediaType, string $message): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage($message);

        new Download($filename, $mediaType, static function ($stream): void {});
    }
}
