<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Testing;

use Contenir\Extension\Api\Testing\FrozenClock;
use Contenir\Extension\Api\Testing\MapUrlResolver;
use Contenir\Extension\Api\Testing\MemoryLogger;
use Contenir\Extension\Api\Url\RecordReference;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

#[Group('unit')]
final class SmallPartsTest extends TestCase
{
    #[Test]
    public function aFrozenClockStaysAtItsMoment(): void
    {
        $now = new DateTimeImmutable('2026-10-19T09:00:00+00:00');

        static::assertSame($now, (new FrozenClock($now))->now());
    }

    #[Test]
    public function aMapResolvesTheUrlsItHolds(): void
    {
        $map  = new MapUrlResolver(['article:1' => 'https://example.com/one']);
        $urls = $map->with(new RecordReference('article', 2), 'https://example.com/two');

        static::assertSame(
            ['https://example.com/one', 'https://example.com/two', null],
            [
                $urls->url(new RecordReference('article', 1)),
                $urls->url(new RecordReference('article', 2)),
                $urls->url(new RecordReference('page', 1)),
            ],
        );
    }

    #[Test]
    public function aMemoryLoggerKeepsEveryEntry(): void
    {
        $logger = new MemoryLogger();
        $logger->info('One', ['a' => 1]);
        $logger->log(3, 'Two');

        static::assertSame(
            [
                ['level' => LogLevel::INFO, 'message' => 'One', 'context' => ['a' => 1]],
                ['level' => 'int', 'message' => 'Two', 'context' => []],
            ],
            $logger->entries(),
        );
    }
}
