<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Record\NewRecord;
use Contenir\Extension\Api\Record\RecordsInterface;
use Contenir\Extension\Api\Record\RecordSummary;
use Contenir\Extension\Api\Record\RecordUpdate;
use Contenir\Extension\Api\Record\RecordUpdatesInterface;
use Contenir\Extension\Api\Url\RecordReference;

use function array_filter;
use function array_keys;
use function ctype_digit;
use function explode;

/**
 * What the probe's records handler answers with: records and a create's
 * result as plain arrays, and the record a query names.
 *
 * @api
 */
final class RecordsAnswer
{
    /**
     * Creates the record the query describes.
     *
     * @param array<string, string> $query
     *
     * @return array{outcome: string, id: string|int|null, errors: list<string>}
     */
    public static function created(RecordsInterface $records, array $query): array
    {
        $result = $records->create(new NewRecord(
            $query['section'] ?? '',
            $query['title'] ?? '',
            $query['path'] ?? '',
            $query['workflow'] ?? null,
        ));

        return ['outcome' => $result->outcome->value, 'id' => $result->id, 'errors' => array_keys($result->errors)];
    }

    /**
     * @return array{record: string, title: string, status: string, path: string|null, edit: bool}|null
     */
    public static function described(?RecordSummary $summary): ?array
    {
        return null === $summary ? null : self::summary($summary);
    }

    /**
     * The record the query names by its section and id, a number when it is
     * all digits.
     *
     * @param array<string, string> $query
     */
    public static function reference(array $query): RecordReference
    {
        $id = $query['id'] ?? '';

        return new RecordReference($query['section'] ?? '', ctype_digit($id) ? (int) $id : $id);
    }

    /**
     * @return array{record: string, title: string, status: string, path: string|null, edit: bool}
     */
    public static function summary(RecordSummary $summary): array
    {
        return [
            'record' => "{$summary->record->section}:{$summary->record->id}",
            'title'  => $summary->title,
            'status' => $summary->status->value,
            'path'   => $summary->path,
            'edit'   => null !== $summary->editUrl,
        ];
    }

    /**
     * What the host's record updates answered for the records the query names, one entry each: the
     * record, the outcome, the previous values and the fields that held it.
     *
     * @param array<string, string> $query
     *
     * @return list<array{record: string, outcome: string, previous: array<string, mixed>, errors: list<string>}>
     *
     * @throws InvalidDeclarationException When the host refuses so many updates in one call.
     */
    public static function updated(RecordUpdatesInterface $updates, array $query): array
    {
        $section = $query['section'] ?? '';
        $values  = [$query['field'] ?? '' => $query['value'] ?? ''];
        $made    = [];
        foreach (array_filter(explode(',', $query['ids'] ?? '')) as $id) {
            $made[] = new RecordUpdate(self::reference(['section' => $section, 'id' => $id]), $values);
        }

        $answers = [];
        foreach ($updates->update($made) as $result) {
            $answers[] = [
                'record'   => "{$result->record->section}:{$result->record->id}",
                'outcome'  => $result->result->outcome->value,
                'previous' => $result->previous,
                'errors'   => array_keys($result->result->errors),
            ];
        }

        return $answers;
    }
}
