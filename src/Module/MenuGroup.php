<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Module;

/**
 * A group of items in a module's section menu, under a heading or none.
 *
 * @api
 */
final readonly class MenuGroup
{
    /**
     * @param list<MenuItem> $items
     */
    public function __construct(
        public ?string $title,
        public array $items,
    ) {}
}
