<?php

declare(strict_types=1);

namespace Contenir\Extension\Example;

/**
 * The signing secret from the add-on's settings, as the host provides it.
 *
 * @api
 */
final readonly class Secret
{
    public function __construct(
        public string $value,
    ) {}
}
