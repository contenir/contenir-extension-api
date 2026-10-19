<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Handler;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;

use function array_diff_key;
use function array_key_first;
use function sprintf;

/**
 * A table described as data, for a described screen.
 *
 * @api
 */
final readonly class Table
{
    /**
     * @param array<string, string>                     $columns Key => heading, in display order
     * @param list<array<string, scalar|null>>          $rows    Values by column key; a missing key shows empty
     *
     * @throws InvalidDeclarationException When a row has a value for a column the table lacks.
     */
    public function __construct(
        public array $columns,
        public array $rows = [],
    ) {
        foreach ($rows as $row) {
            $unknown = array_key_first(array_diff_key($row, $columns));
            if (null !== $unknown) {
                throw InvalidDeclarationException::because(sprintf(
                    'A row has a value for "%s", which is not a column',
                    $unknown,
                ));
            }
        }
    }
}
