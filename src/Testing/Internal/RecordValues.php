<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Record\RecordRow;
use Contenir\Extension\Api\Record\RecordStatus;
use Contenir\Extension\Api\Record\RecordSummary;
use Contenir\Extension\Api\Url\RecordReference;

use function is_array;
use function is_scalar;
use function is_string;
use function json_encode;
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
     * A record's values for $columns, with its status: its "status" value,
     * or published when it has none.
     *
     * @param array<string, mixed> $values
     * @param list<string>         $columns
     */
    public static function row(string $section, string|int $id, array $values, array $columns): RecordRow
    {
        $status = is_string($values['status'] ?? null) ? RecordStatus::tryFrom($values['status']) : null;
        $read   = [];
        foreach ($columns as $column) {
            $read[$column] = self::scalar($values[$column] ?? null);
        }

        return new RecordRow(new RecordReference($section, $id), $status ?? RecordStatus::Published, $read);
    }

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

    /**
     * A stored value as a column holds it: an array as its JSON, anything
     * else that isn't scalar, or an array that can't be encoded, as nothing.
     */
    private static function scalar(mixed $value): string|int|float|bool|null
    {
        if (! is_array($value)) {
            return is_scalar($value) ? $value : null;
        }

        $json = json_encode($value);

        return false === $json ? null : $json;
    }
}
