<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Module;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * An item in a module's section menu, leading to one of its handlers. The
 * host shows it only to someone allowed to view that handler.
 *
 * @api
 */
final readonly class MenuItem
{
    /**
     * @param array<string, scalar> $query
     *
     * @throws InvalidDeclarationException When the label is empty or the handler name malformed.
     */
    public function __construct(
        public string $label,
        public string $handler,
        public array $query = [],
        public bool $current = false,
    ) {
        Names::text('A menu item label', $label);
        Names::name('Handler', $handler);
    }
}
