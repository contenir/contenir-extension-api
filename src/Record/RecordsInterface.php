<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Record;

use Contenir\Extension\Api\Event\ActionResult;
use Contenir\Extension\Api\Url\RecordReference;

/**
 * The site's records, lent to add-ons that link to them, such as a menu's
 * items: the sections the site marks as linkable, searched, described and
 * added to. A host that has records offers them from each add-on's
 * services(), named by this interface; not every host does, so an add-on
 * asks has() first.
 *
 * Only linkable sections are searched or created in, and creating needs the
 * signed-in editor's permission to add to the section.
 *
 * @api
 */
interface RecordsInterface
{
    /**
     * Creates a draft record through the host's normal save path, so its
     * validation, the extension events, holds and the editor's permission to
     * add all apply. When the add-on has a transaction open on the site's
     * database the record is written inside it, and rolls back with it;
     * otherwise the host commits it alone.
     *
     * The result is the save's: Done with the new record's id, or Stopped,
     * with field errors such as a path already in use under "path", or
     * Failed.
     */
    public function create(NewRecord $record): ActionResult;

    /**
     * A record's title, status, path and edit URL, or null when there is no
     * such record or its section isn't linkable.
     */
    public function describe(RecordReference $record): ?RecordSummary;

    /**
     * Whether no record already has $path, such as /about/our-team, so a new
     * one can take it.
     */
    public function isPathFree(string $path): bool;

    /**
     * Records whose title or path holds $text, ignoring case, in the linkable
     * sections named, or in all of them when none are, ordered as sections()
     * lists them and then by title. At most $limit are returned.
     *
     * @param list<string> $sections
     *
     * @return list<RecordSummary>
     */
    public function search(string $text, array $sections = [], int $limit = 20): array;

    /**
     * The sections the site marks as linkable, in the site's order.
     *
     * @return list<LinkableSection>
     */
    public function sections(): array;
}
