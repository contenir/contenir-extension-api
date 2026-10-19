<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Form;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;

use function array_key_exists;
use function sprintf;

/**
 * A form described as data, which the host draws in its own theme: a
 * settings section's fields, a guided fix's inputs or a described screen.
 * With no groups, the host shows every field in one default group.
 *
 * @api
 */
final readonly class Form
{
    /**
     * @var array<string, Field>
     */
    private array $byName;

    /**
     * @param list<Field> $fields
     * @param list<Group> $groups
     *
     * @throws InvalidDeclarationException When two fields or groups share a name, or a group lays out a field the form lacks.
     */
    public function __construct(
        public array $fields = [],
        public array $groups = [],
    ) {
        $byName = [];
        foreach ($fields as $field) {
            if (array_key_exists($field->name, $byName)) {
                throw InvalidDeclarationException::duplicate('field', $field->name);
            }
            $byName[$field->name] = $field;
        }
        $this->byName = $byName;

        $ids = [];
        foreach ($groups as $group) {
            if (array_key_exists($group->id, $ids)) {
                throw InvalidDeclarationException::duplicate('group', $group->id);
            }
            $ids[$group->id] = $group;

            foreach ($group->fieldNames() as $name) {
                if (! array_key_exists($name, $byName)) {
                    throw InvalidDeclarationException::because(
                        sprintf('Group "%s" lays out "%s", which is not a field of the form', $group->id, $name),
                    );
                }
            }
        }
    }

    /**
     * The field named $name, or null when the form has none.
     */
    public function field(string $name): ?Field
    {
        return $this->byName[$name] ?? null;
    }
}
