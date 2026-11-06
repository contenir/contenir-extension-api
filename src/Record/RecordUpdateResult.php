<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Record;

use Contenir\Extension\Api\Event\ActionResult;
use Contenir\Extension\Api\Url\RecordReference;

/**
 * What came of one RecordUpdate: the record, the save's own result (Done, Stopped with field errors, or
 * Failed), and, when it was Done, the values the changed fields held before, by field, so an add-on can
 * keep them for an undo. A field that had no value before holds null.
 *
 * @api
 */
final readonly class RecordUpdateResult
{
    /**
     * @param array<string, mixed> $previous The changed fields' values before the update; empty unless Done
     */
    public function __construct(
        public RecordReference $record,
        public ActionResult $result,
        public array $previous = [],
    ) {}

    public function succeeded(): bool
    {
        return $this->result->succeeded();
    }
}
