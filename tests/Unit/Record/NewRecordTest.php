<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Record;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Record\NewRecord;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class NewRecordTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function emptyProvider(): array
    {
        return [
            'no section' => ['', 'Contact', '/contact', 'A new record section must not be empty'],
            'no title'   => ['page', '', '/contact', 'A new record title must not be empty'],
            'no path'    => ['page', 'Contact', '', 'A new record path must not be empty'],
        ];
    }

    #[Test]
    public function hasNoWorkflowOrParentUnlessItNamesThem(): void
    {
        $record = new NewRecord('page', 'Contact', '/contact');

        static::assertSame([null, null], [$record->workflow, $record->parent]);
    }

    #[Test]
    #[DataProvider('emptyProvider')]
    public function needsASectionATitleAndAPath(string $section, string $title, string $path, string $message): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage($message);

        new NewRecord($section, $title, $path);
    }
}
