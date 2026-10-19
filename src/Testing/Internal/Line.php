<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

/**
 * A bound listener at its position in the order.
 *
 * @internal
 */
final readonly class Line
{
    public function __construct(
        public int $position,
        public BoundListener $listener,
    ) {}
}
