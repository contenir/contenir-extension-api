<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Form;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

use function array_merge;
use function is_string;

/**
 * A titled group of a form's fields, laid out in rows. A row holds one field,
 * or several drawn side by side as columns.
 *
 * @api
 */
final readonly class Group
{
    /**
     * @var list<list<string>>
     */
    public array $rows;

    /**
     * @param list<string|list<string>> $rows A field name for a one-column row, or a list of names for columns
     *
     * @throws InvalidDeclarationException When the id is malformed.
     */
    public function __construct(
        public string $id,
        public string $legend,
        array $rows,
    ) {
        Names::field($id);

        $normalised = [];
        foreach ($rows as $row) {
            $normalised[] = is_string($row) ? [$row] : $row;
        }

        $this->rows = $normalised;
    }

    /**
     * Every field the group lays out, in order.
     *
     * @return list<string>
     */
    public function fieldNames(): array
    {
        return array_merge(...$this->rows);
    }
}
