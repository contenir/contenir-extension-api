<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Exception\BlockNotSavedException;
use Contenir\Extension\Api\Exception\RowsRefusedException;
use Contenir\Extension\Api\Section\Element;
use Contenir\Extension\Api\Section\SectionBlock;
use Contenir\Extension\Api\Section\SectionsInterface;
use Override;

/**
 * The sections of an add-on that has no section blocks: none, and no rows.
 *
 * @internal
 *
 * @mago-expect lint:too-many-methods One method per method of SectionsInterface.
 */
final readonly class NoSections implements SectionsInterface
{
    #[Override]
    public function block(string $section): ?SectionBlock
    {
        return null;
    }

    #[Override]
    public function blocks(): array
    {
        return [];
    }

    #[Override]
    public function declared(string $section): ?SectionBlock
    {
        return null;
    }

    #[Override]
    public function overridden(string $section): bool
    {
        return false;
    }

    #[Override]
    public function override(string $section, Element $block, string $version): void
    {
        throw BlockNotSavedException::refused("Section {$section} has no block to override");
    }

    #[Override]
    public function overridesVersion(): string
    {
        return '';
    }

    #[Override]
    public function restore(string $section, string $version): void {}

    /**
     * Refuses every read, so its limit and offset never matter.
     *
     * @infection-ignore-all Its defaults only mirror the interface's; no value of them changes what it does.
     */
    #[Override]
    public function rows(string $section, array $columns, array $ids = [], int $limit = 100, int $offset = 0): array
    {
        throw RowsRefusedException::section('', $section);
    }

    #[Override]
    public function save(string $section, Element $block, string $version): void
    {
        throw BlockNotSavedException::notAllowed();
    }

    #[Override]
    public function saves(): bool
    {
        return false;
    }

    #[Override]
    public function version(): string
    {
        return '';
    }
}
