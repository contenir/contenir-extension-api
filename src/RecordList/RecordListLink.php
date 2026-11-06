<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\RecordList;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * A link on a {@see RecordList} row, drawn as an icon with its label as the tooltip.
 *
 * @api
 */
final readonly class RecordListLink
{
    /**
     * @throws InvalidDeclarationException When the label, URL or icon is empty.
     */
    public function __construct(
        public string $label,
        public string $url,
        public string $icon,
        public bool $newTab = false,
    ) {
        Names::text('A record list link label', $label);
        Names::text('A record list link URL', $url);
        Names::text('A record list link icon', $icon);
    }
}
