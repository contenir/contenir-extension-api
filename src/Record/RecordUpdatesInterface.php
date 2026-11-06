<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Record;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;

/**
 * Changes to saved records, lent to add-ons that fix many at once, such as SEO's bulk descriptions. A host
 * that has them offers them from each add-on's services(), named by this interface; not every host does, so
 * an add-on asks has() first and offers no bulk tool without it.
 *
 * Each record goes through the host's normal save, so validation, the extension events and holds all run,
 * and the signed-in editor needs the permission to update it. Each succeeds or fails on its own: one that
 * is stopped or fails never keeps the others from being saved, and nothing is saved in one transaction.
 *
 * @api
 */
interface RecordUpdatesInterface
{
    /**
     * The most records one call takes. An add-on with more splits them into calls of this many.
     */
    public const int MAX_RECORDS = 50;

    /**
     * Saves each update, in the order given, and answers one result for each, in the same order. A result
     * is the save's: Done, with the values the changed fields held before, or Stopped, with field errors,
     * or Failed.
     *
     * @param list<RecordUpdate> $updates
     *
     * @return list<RecordUpdateResult>
     *
     * @throws InvalidDeclarationException When there are more than MAX_RECORDS updates.
     */
    public function update(array $updates): array;
}
