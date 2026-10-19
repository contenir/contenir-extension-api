<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Log;

use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Log\SourceLogger;
use Contenir\Extension\Api\Tests\TestAsset\Log\RecordingLogger;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

#[Group('unit')]
final class SourceLoggerTest extends TestCase
{
    #[Test]
    public function keepsTheStampOverTheAddOnsOwnSource(): void
    {
        $inner = new RecordingLogger();
        (new SourceLogger($inner, new Handle('newsletter')))->log(LogLevel::ERROR, 'Failed', ['source' => 'core']);

        static::assertSame(['source' => 'extension.newsletter'], $inner->records[0]['context'] ?? null);
    }

    #[Test]
    public function stampsEntriesWithTheAddOn(): void
    {
        $inner = new RecordingLogger();
        (new SourceLogger($inner, new Handle('newsletter')))->warning('Sync slow', ['list' => 'main']);

        static::assertSame(
            [[
                'level'   => LogLevel::WARNING,
                'message' => 'Sync slow',
                'context' => [
                    'list'   => 'main',
                    'source' => 'extension.newsletter',
                ],
            ]],
            $inner->records,
        );
    }
}
