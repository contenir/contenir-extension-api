<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Handler;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * A tab on a described screen, leading to one of the add-on's handlers: the
 * screens about one record, such as a form's entries, fields and settings,
 * share a row of them under the page header.
 *
 * @api
 */
final readonly class Tab
{
    /**
     * @param array<string, scalar> $query
     *
     * @throws InvalidDeclarationException When the label is empty or the handler or icon name malformed.
     */
    public function __construct(
        public string $label,
        public string $handler,
        public array $query = [],
        public bool $current = false,
        public ?string $icon = null,
    ) {
        Names::text('A tab label', $label);
        Names::name('Handler', $handler);
        if (null !== $icon) {
            Names::name('Icon', $icon);
        }
    }
}
