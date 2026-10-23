<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Record\RecordStatus;
use Contenir\Extension\Api\Record\RecordSummary;
use Contenir\Extension\Api\Url\RecordReference;

use function is_string;
use function sprintf;

/**
 * Summarises an in-memory record from its "title", "path" and "status"
 * values. One without a status, or with one that isn't a RecordStatus, is
 * published.
 *
 * @internal
 */
final class RecordValues
{
    /**
     * @param array<string, mixed> $values
     */
    public static function summary(RecordReference $record, array $values): RecordSummary
    {
        $status = is_string($values['status'] ?? null) ? RecordStatus::tryFrom($values['status']) : null;

        return new RecordSummary(
            $record,
            is_string($values['title'] ?? null) ? $values['title'] : '',
            $status ?? RecordStatus::Published,
            is_string($values['path'] ?? null) ? $values['path'] : null,
            sprintf('/admin/records/%s/%s', $record->section, $record->id),
        );
    }
}
