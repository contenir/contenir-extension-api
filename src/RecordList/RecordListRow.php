<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\RecordList;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * One record in a {@see RecordList}.
 *
 * @api
 */
final readonly class RecordListRow
{
    /**
     * @param string $id What the row's box posts when it's ticked
     * @param string $label What a screen reader calls the row, usually the record's title
     * @param list<RecordListCell> $cells One for each of the list's headings
     * @param list<RecordListLink> $actions The row's own links, the first shown first
     *
     * @throws InvalidDeclarationException When the id or label is empty.
     */
    public function __construct(
        public string $id,
        public string $label,
        public array $cells,
        public array $actions = [],
    ) {
        Names::text('A record list row id', $id);
        Names::text('A record list row label', $label);
    }
}
