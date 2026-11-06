<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Record;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;
use Contenir\Extension\Api\Url\RecordReference;

use function array_keys;
use function count;

/**
 * A change an add-on asks the host to make to one saved record: the record, and the values to set,
 * by field. Fields the change leaves out keep their values.
 *
 * @api
 */
final readonly class RecordUpdate
{
    /**
     * @param array<string, mixed> $values The fields to set, by name
     *
     * @throws InvalidDeclarationException When no field is named, or a field name is not valid.
     */
    public function __construct(
        public RecordReference $record,
        public array $values,
    ) {
        if (0 === count($values)) {
            throw InvalidDeclarationException::because('A record update must set at least one field');
        }

        foreach (array_keys($values) as $field) {
            Names::field((string) $field);
        }
    }
}
