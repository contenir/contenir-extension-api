<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Record;

use Contenir\Extension\Api\Record\RecordStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class RecordStatusTest extends TestCase
{
    /**
     * @return array<string, array{RecordStatus, bool}>
     */
    public static function statusProvider(): array
    {
        return [
            'published'   => [RecordStatus::Published, true],
            'draft'       => [RecordStatus::Draft, false],
            'unpublished' => [RecordStatus::Unpublished, false],
            'archived'    => [RecordStatus::Archived, false],
        ];
    }

    #[Test]
    #[DataProvider('statusProvider')]
    public function onlyAPublishedRecordIsLive(RecordStatus $status, bool $live): void
    {
        static::assertSame($live, $status->isLive());
    }
}
