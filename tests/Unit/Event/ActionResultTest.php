<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Event;

use Contenir\Extension\Api\Event\ActionResult;
use Contenir\Extension\Api\Event\Outcome;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ActionResultTest extends TestCase
{
    #[Test]
    public function saysNothingMoreByDefault(): void
    {
        $result = new ActionResult(Outcome::Done, 1);

        static::assertSame(['', '', [], [], []], [
            $result->message,
            $result->detail,
            $result->errors,
            $result->warnings,
            $result->flags,
        ]);
    }

    #[Test]
    public function succeedsOnlyWhenDone(): void
    {
        static::assertSame(
            [true, false, false],
            [
                (new ActionResult(Outcome::Done, 1))->succeeded(),
                (new ActionResult(Outcome::Stopped, 1))->succeeded(),
                (new ActionResult(Outcome::Failed, 1))->succeeded(),
            ],
        );
    }
}
