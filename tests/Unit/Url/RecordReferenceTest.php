<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Url;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Url\RecordReference;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class RecordReferenceTest extends TestCase
{
    #[Test]
    public function namesARecordBySectionAndId(): void
    {
        $record = new RecordReference('article', 12);

        static::assertSame(['article', 12], [$record->section, $record->id]);
    }

    #[Test]
    public function needsASection(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('A record reference section must not be empty');

        new RecordReference('', 12);
    }
}
