<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Module;

/**
 * A dashboard JavaScript component an add-on ships: the selector of the
 * elements it enhances, and its script, relative to the add-on's assets.
 *
 * @api
 */
final readonly class Component
{
    public function __construct(
        public string $selector,
        public string $script,
    ) {}
}
