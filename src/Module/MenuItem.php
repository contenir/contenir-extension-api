<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Module;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * An item in a module's section menu, leading to one of its handlers. The
 * host shows it only to someone allowed to view that handler, with its count,
 * such as how many items a menu holds, beside the label.
 *
 * @api
 */
final readonly class MenuItem
{
    /**
     * @param array<string, scalar> $query
     * @param ?int                  $count How many of something the item leads to, or null to show no count
     *
     * @throws InvalidDeclarationException When the label is empty, the handler name malformed or the count negative.
     */
    public function __construct(
        public string $label,
        public string $handler,
        public array $query = [],
        public bool $current = false,
        public ?int $count = null,
    ) {
        Names::text('A menu item label', $label);
        Names::name('Handler', $handler);
        if (null !== $count && $count < 0) {
            throw InvalidDeclarationException::because("A menu item's count must not be negative, {$count} given");
        }
    }
}
