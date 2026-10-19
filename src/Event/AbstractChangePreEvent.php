<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event;

use Override;

/**
 * A Pre event about values that are about to change. Its listeners see the
 * values as the last listener left them, and may change them.
 *
 * @api
 */
abstract class AbstractChangePreEvent extends AbstractPreEvent implements ChangeEventInterface
{
    public function __construct(
        private Change $change,
    ) {}

    #[Override]
    public function change(): Change
    {
        return $this->change;
    }

    /**
     * Changes a value before it is written.
     */
    public function setValue(string $field, mixed $value): void
    {
        $this->change = $this->change->with($field, $value);
    }

    /**
     * The values about to be written.
     *
     * @return array<string, mixed>
     */
    public function values(): array
    {
        return $this->change->after;
    }
}
