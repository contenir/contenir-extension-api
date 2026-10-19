<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Diagnostic;

use Contenir\Extension\Api\Diagnostic\CheckResult;
use Contenir\Extension\Api\Diagnostic\GuidedFix;
use Contenir\Extension\Api\Diagnostic\OneClickFix;
use Contenir\Extension\Api\Diagnostic\Status;
use Contenir\Extension\Api\Tests\TestAsset\Extension\StoreApiKey;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class CheckResultTest extends TestCase
{
    #[Test]
    public function failsWithAGuidedFix(): void
    {
        $fix    = new GuidedFix('Add the key', 'Get a key from your account page.');
        $result = CheckResult::failure('No API key', $fix);

        static::assertSame([Status::Failure, 'No API key', $fix], [$result->status, $result->message, $result->fix]);
    }

    #[Test]
    public function skipsWithAReason(): void
    {
        $result = CheckResult::skipped('Not connected yet');

        static::assertSame([Status::Skipped, 'Not connected yet', null], [
            $result->status,
            $result->message,
            $result->fix,
        ]);
    }

    #[Test]
    public function succeedsWithAnEmptyMessageByDefault(): void
    {
        static::assertSame('', CheckResult::success()->message);
    }

    #[Test]
    public function succeedsWithoutAFix(): void
    {
        $result = CheckResult::success('Connected');

        static::assertSame([Status::Success, 'Connected', null], [$result->status, $result->message, $result->fix]);
    }

    #[Test]
    public function warnsAndFailsWithoutAFixByDefault(): void
    {
        static::assertSame([null, null], [CheckResult::warning('Slow')->fix, CheckResult::failure('Down')->fix]);
    }

    #[Test]
    public function warnsWithAFix(): void
    {
        $fix    = new OneClickFix('Reconnect', StoreApiKey::class);
        $result = CheckResult::warning('Token expires soon', $fix);

        static::assertSame([Status::Warning, 'Token expires soon', $fix], [
            $result->status,
            $result->message,
            $result->fix,
        ]);
    }
}
