<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

/**
 * A bound listener at its position in the order, and whether a section
 * block implied it rather than the site writing it.
 *
 * @internal
 */
final readonly class Line
{
    public function __construct(
        public int $position,
        public BoundListener $listener,
        public bool $implied = false,
    ) {}

    /**
     * Whether this line binds the same add-on's listener of the same name as $listener.
     */
    public function binds(BoundListener $listener): bool
    {
        return (
            $listener->handle->value === $this->listener->handle->value
                && $listener->declaration->name === $this->listener->declaration->name
        );
    }
}
