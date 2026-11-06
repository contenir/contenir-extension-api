<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\RecordList;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * Something a {@see RecordList} can do to its ticked rows: a button that posts them to the list's bulk
 * URL with $name set to $value, shown only while a row is ticked.
 *
 * @api
 */
final readonly class RecordListAction
{
    /**
     * @throws InvalidDeclarationException When the label, icon or name is empty.
     */
    public function __construct(
        public string $label,
        public string $icon,
        public string $name,
        public string $value,
    ) {
        Names::text('A record list action label', $label);
        Names::text('A record list action icon', $icon);
        Names::text('A record list action name', $name);
    }
}
