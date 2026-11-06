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
