<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Record;

use Contenir\Extension\Api\Url\RecordReference;

/**
 * One record's values for the columns an add-on asked for, with where it
 * stands. A column the record holds nothing in is null.
 *
 * @api
 */
final readonly class RecordRow
{
    /**
     * @param array<string, scalar|null> $values By column, in the order asked for
     */
    public function __construct(
        public RecordReference $record,
        public RecordStatus $status,
        public array $values,
    ) {}

    /**
     * A column's value, or null when it holds nothing or was not asked for.
     */
    public function value(string $column): string|int|float|bool|null
    {
        return $this->values[$column] ?? null;
    }
}
