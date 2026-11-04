<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Closure;
use Contenir\Extension\Api\Exception\RowsRefusedException;
use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Record\RecordRow;
use Contenir\Extension\Api\Section\SectionBlock;
use Contenir\Extension\Api\Section\SectionsInterface;
use Override;

use function array_filter;
use function array_keys;
use function array_map;
use function array_slice;
use function array_values;
use function in_array;
use function ksort;

use const SORT_NATURAL;

/**
 * One add-on's section blocks in the in-memory host, and the values of the
 * columns they read, as RecordValues::row() gives them.
 *
 * @internal
 */
final readonly class AddOnSections implements SectionsInterface
{
    /**
     * @param Closure(SectionBlock): list<string> $columns  The columns a block reads
     * @param Closure(string): bool               $linkable Whether a section is linkable
     */
    public function __construct(
        private Handle $handle,
        private SectionBlocks $blocks,
        private Records $records,
        private Closure $columns,
        private Closure $linkable,
    ) {}

    #[Override]
    public function block(string $section): ?SectionBlock
    {
        foreach ($this->blocks() as $block) {
            if ($section === $block->section) {
                return $block;
            }
        }

        return null;
    }

    #[Override]
    public function blocks(): array
    {
        $found = [];
        foreach ($this->blocks->for($this->handle->value) as $section => $element) {
            $found[] = new SectionBlock(
                $section,
                $element,
                $this->blocks->fields($section),
                ($this->linkable)($section),
            );
        }

        return $found;
    }

    #[Override]
    public function rows(string $section, array $columns, array $ids = [], int $limit = 100, int $offset = 0): array
    {
        $block = $this->block($section) ?? throw RowsRefusedException::section($this->handle->value, $section);
        $reads = ($this->columns)($block);
        foreach ($columns as $column) {
            if (! in_array($column, $reads, strict: true)) {
                throw RowsRefusedException::column($section, $column);
            }
        }

        if ($limit < 0 || $offset < 0) {
            throw RowsRefusedException::range($limit, $offset);
        }

        $stored = $this->records->all()[$section] ?? [];
        ksort($stored, SORT_NATURAL);
        $wanted = array_map(strval(...), $ids);
        $chosen = [] === $ids
            ? array_slice(array_keys($stored), $offset, $limit)
            : array_values(array_filter(
                array_keys($stored),
                static fn(string|int $id): bool => in_array((string) $id, $wanted, strict: true),
            ));

        return array_map(
            static fn(string|int $id): RecordRow => RecordValues::row($section, $id, $stored[$id] ?? [], $columns),
            $chosen,
        );
    }
}
