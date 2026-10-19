<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Testing\Internal;

use Contenir\Extension\Api\Exception\DispatchRefusedException;
use Contenir\Extension\Api\Testing\Internal\Chain;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ChainTest extends TestCase
{
    #[Test]
    public function letsAnEventRunAgainOnceItHasLeft(): void
    {
        $chain = new Chain();
        $chain->enter('Announced');
        $chain->leave();
        $chain->enter('Announced');

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function letsEventsNestUpToItsDepth(): void
    {
        $chain = new Chain();
        for ($depth = 1; $depth <= Chain::MAX_DEPTH; ++$depth) {
            $chain->enter("Event{$depth}");
        }

        $this->expectException(DispatchRefusedException::class);
        $this->expectExceptionMessage('Event9 would make the chain 9 events deep');

        $chain->enter('Event9');
    }

    #[Test]
    public function refusesAnEventAlreadyRunning(): void
    {
        $chain = new Chain();
        $chain->enter('Announced');
        $chain->enter('Other');

        $this->expectException(DispatchRefusedException::class);
        $this->expectExceptionMessage('Announced is already running in this chain');

        $chain->enter('Announced');
    }
}
