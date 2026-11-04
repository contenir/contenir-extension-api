<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Section;

use Contenir\Extension\Api\Exception\RowsRefusedException;
use Contenir\Extension\Api\Record\RecordRow;

/**
 * The sections that carry the add-on's block, and the values of the columns
 * each block reads. An add-on gets its own from its context; it sees only
 * its own blocks, and only while it is enabled and declared sectionBlock().
 *
 * @api
 */
interface SectionsInterface
{
    /**
     * The add-on's block on $section, or null when the section has none.
     */
    public function block(string $section): ?SectionBlock;

    /**
     * Every section block of the add-on's, in the order admin.xml lists the
     * sections.
     *
     * @return list<SectionBlock>
     */
    public function blocks(): array;

    /**
     * Records of $section with the values of $columns, drafts included, by
     * id. With $ids, only those records; otherwise up to $limit of them,
     * after skipping $offset.
     *
     * @param list<string>     $columns
     * @param list<string|int> $ids
     *
     * @return list<RecordRow>
     *
     * @throws RowsRefusedException When $section has no block of the add-on's, a column is not one its block reads, or $limit or $offset is negative.
     */
    public function rows(string $section, array $columns, array $ids = [], int $limit = 100, int $offset = 0): array;
}
