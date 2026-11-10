<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Section;

use Contenir\Extension\Api\Exception\BlockNotSavedException;
use Contenir\Extension\Api\Exception\RowsRefusedException;
use Contenir\Extension\Api\Record\RecordRow;

/**
 * The sections that carry the add-on's block, and the values of the columns
 * each block reads. An add-on gets its own from its context; it sees only
 * its own blocks, and only while it is enabled and declared sectionBlock().
 *
 * A block is the one admin.xml puts on the section, unless the add-on has
 * overridden it on this environment: the host keeps the override beside the
 * site's data, on a live site as anywhere else, and from then on gives it in
 * the block's place, reads the columns it names and binds the listeners it
 * implies. An override only stands in for a block admin.xml puts there, so
 * it never puts the add-on on a section, and the host lends an overridden
 * block only the columns that are fields of its section.
 *
 * @api
 *
 * @mago-expect lint:too-many-methods Blocks, their rows, saving one into admin.xml and overriding one are a single view of the add-on's sections.
 */
interface SectionsInterface
{
    /**
     * The add-on's block on $section, with its override in its place where it
     * has one, or null when the section has none.
     */
    public function block(string $section): ?SectionBlock;

    /**
     * Every section block of the add-on's, each with its override in its
     * place where it has one, in the order admin.xml lists the sections.
     *
     * @return list<SectionBlock>
     */
    public function blocks(): array;

    /**
     * The add-on's block on $section as admin.xml gives it, whatever override
     * the section has, or null when admin.xml puts none there.
     */
    public function declared(string $section): ?SectionBlock;

    /**
     * Whether the add-on's block on $section is an override and not admin.xml's.
     */
    public function overridden(string $section): bool;

    /**
     * Puts $block in the place of the add-on's block on $section, on this
     * environment only, leaving admin.xml as it is.
     *
     * @param string $version What overridesVersion() gave when the add-on read the block it changed
     *
     * @throws BlockNotSavedException When admin.xml puts no block of the add-on's on $section, $block is not named after the add-on, the overrides have changed since $version, or the host can't keep it.
     */
    public function override(string $section, Element $block, string $version): void;

    /**
     * The version of the add-on's overrides as they are now, which an
     * override or a restore asks to find there still. It is apart from
     * version(), which admin.xml alone moves.
     */
    public function overridesVersion(): string;

    /**
     * Returns the add-on's block on $section to what admin.xml says. A
     * section with no override is left as it is.
     *
     * @param string $version What overridesVersion() gave when the add-on read the block
     *
     * @throws BlockNotSavedException When the overrides have changed since $version, or the host can't keep the change.
     */
    public function restore(string $section, string $version): void;

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
     * @throws RowsRefusedException When $section has no block of the add-on's, a column is not one its block reads or, for an overridden block, not a field of the section, or $limit or $offset is negative.
     */
    public function rows(string $section, array $columns, array $ids = [], int $limit = 100, int $offset = 0): array;

    /**
     * Replaces the add-on's block on $section with $block, leaving the rest of the section as it is. The
     * block takes the add-on's name, and the section gets the block if it had none.
     *
     * @param string $version What version() gave when the add-on read the block it changed
     *
     * @throws BlockNotSavedException When the host does not save here, the configuration has changed since $version, or the result would not load.
     */
    public function save(string $section, Element $block, string $version): void;

    /**
     * Whether the host saves a block from here. A site's configuration saves only where it is developed,
     * so on a live site this is false and a save is refused.
     */
    public function saves(): bool;

    /**
     * The version of the site's configuration as it is now, which a save asks to find there still.
     */
    public function version(): string;
}
