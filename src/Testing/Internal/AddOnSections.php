<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Closure;
use Contenir\Extension\Api\Exception\BlockNotSavedException;
use Contenir\Extension\Api\Exception\RowsRefusedException;
use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Record\RecordRow;
use Contenir\Extension\Api\Section\Element;
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
use function sprintf;

use const SORT_NATURAL;

/**
 * One add-on's section blocks in the in-memory host, and the values of the
 * columns they read, as RecordValues::row() gives them.
 *
 * @internal
 *
 * @mago-expect lint:too-many-methods One method per method of SectionsInterface.
 */
final readonly class AddOnSections implements SectionsInterface
{
    /**
     * @param Closure(SectionBlock): list<string> $columns  The columns a block reads
     * @param Closure(string): bool               $linkable Whether a section is linkable
     *
     * @mago-expect lint:excessive-parameter-list One parameter per part of the host a block reaches.
     */
    public function __construct(
        private Handle $handle,
        private SectionBlocks $blocks,
        private Records $records,
        private Closure $columns,
        private Closure $linkable,
        private BlockOverrides $overrides = new BlockOverrides(),
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
        $declared = $this->blocks->for($this->handle->value);
        $found    = [];
        foreach ($this->overrides->effective($this->handle->value, $declared) as $section => $element) {
            $found[] = $this->made($section, $element);
        }

        return $found;
    }

    #[Override]
    public function declared(string $section): ?SectionBlock
    {
        $element = $this->blocks->for($this->handle->value)[$section] ?? null;

        return null === $element ? null : $this->made($section, $element);
    }

    #[Override]
    public function overridden(string $section): bool
    {
        return $this->overrides->overrides($this->handle->value, $section);
    }

    #[Override]
    public function override(string $section, Element $block, string $version): void
    {
        $this->overrides->keep(
            $this->handle->value,
            $section,
            $block,
            $version,
            $this->blocks->for($this->handle->value),
        );
    }

    #[Override]
    public function overridesVersion(): string
    {
        return $this->overrides->version($this->handle->value);
    }

    #[Override]
    public function restore(string $section, string $version): void
    {
        $this->overrides->drop($this->handle->value, $section, $version);
    }

    #[Override]
    public function rows(string $section, array $columns, array $ids = [], int $limit = 100, int $offset = 0): array
    {
        $block = $this->block($section) ?? throw RowsRefusedException::section($this->handle->value, $section);
        $reads = $this->overrides->lent($this->handle->value, $section, ($this->columns)($block), $block->fields);
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

    #[Override]
    public function save(string $section, Element $block, string $version): void
    {
        if ($version !== $this->version()) {
            throw BlockNotSavedException::changed();
        }

        if ($block->name !== $this->handle->value) {
            throw BlockNotSavedException::refused(sprintf(
                'A block on %s is named %s, not %s',
                $section,
                $this->handle->value,
                $block->name,
            ));
        }

        $this->blocks->put($section, $block, $this->blocks->fields($section));
    }

    #[Override]
    public function saves(): bool
    {
        return true;
    }

    #[Override]
    public function version(): string
    {
        return (string) $this->blocks->revision();
    }

    private function made(string $section, Element $element): SectionBlock
    {
        return new SectionBlock($section, $element, $this->blocks->fields($section), ($this->linkable)($section));
    }
}
