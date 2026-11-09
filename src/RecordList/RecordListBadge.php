<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\RecordList;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * A short label before a line of a {@see RecordListCell}, such as "High", coloured by its tone.
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
