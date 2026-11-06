<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Field;

/**
 * A field type that says how its value reads on a record's View page, where the host would otherwise draw
 * what is stored as plain text: lines, each labelled, such as a JSON value's keys by name. The host draws
 * them and escapes all of it. A host without this leaves the value as plain text.
 *
 * @api
 */
interface DisplayedFieldTypeInterface extends FieldTypeInterface
{
    /**
     * @param array<string, mixed> $record The record's values by column name, for a line only its other columns give
     *
     * @return list<FieldDisplayLine> None to draw the value as plain text
     */
    public function display(mixed $value, array $record): array;
}
