<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\RecordList;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * How serious a line of a {@see RecordListCell} is, such as "High": the host draws a dot in the tone's colour
 * before the line, with the label as its name.
 *
 * @api
 */
final readonly class RecordListBadge
{
    /**
     * @throws InvalidDeclarationException When the label is empty.
     */
    public function __construct(
        public string $label,
        public RecordListTone $tone,
    ) {
        Names::text('A record list badge label', $label);
    }
}
