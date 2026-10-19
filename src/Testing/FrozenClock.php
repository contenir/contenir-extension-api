<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing;

use DateTimeImmutable;
use Override;
use Psr\Clock\ClockInterface;

/**
 * A PSR-20 clock stopped at one moment, for tests.
 *
 * @api
 */
final readonly class FrozenClock implements ClockInterface
{
    public function __construct(
        private DateTimeImmutable $now,
    ) {}

    #[Override]
    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
