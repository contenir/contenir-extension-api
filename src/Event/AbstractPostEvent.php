<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event;

use Override;

/**
 * The outcome every Post event carries.
 *
 * @api
 */
abstract readonly class AbstractPostEvent implements PostEventInterface
{
    public function __construct(
        private Outcome $outcome = Outcome::Done,
        private string $reason = '',
    ) {}

    #[Override]
    public function outcome(): Outcome
    {
        return $this->outcome;
    }

    #[Override]
    public function reason(): string
    {
        return $this->reason;
    }

    #[Override]
    public function succeeded(): bool
    {
        return Outcome::Done === $this->outcome;
    }
}
