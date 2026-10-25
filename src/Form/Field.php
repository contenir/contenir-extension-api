<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Form;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

use function array_key_exists;
use function is_string;
use function sprintf;

/**
 * One field of a described form: a settings section, a guided fix or a
 * described screen. The host draws it with its own control for the type.
 *
 * @api
 */
final readonly class Field
{
    /**
     * A built-in type's value, or the name of a field type an add-on registered.
     */
    public string $type;

    /**
     * @param array<string, string>                $options    Choices for select, radio and multi-choice types, value => label
     * @param list<Field>                          $fields     The sub-fields of a repeating type such as multilink
     * @param array<string, scalar>                $attributes Hints a host may honour (rows, placeholder, warning: a
     *                                                     note shown beside the label, …); unknown ones are ignored
     * @param ?string                              $setting    The dotted config key a settings section stores the value under; null keeps the field's own name
     * @param bool                                 $transient  Not stored by the host: a row hydrator supplies the value and a save listener keeps it
     * @param bool                                 $local      Stored in the site's .local.php, never in a file that is committed
     *
     * @throws InvalidDeclarationException When the name is malformed or two sub-fields share a name.
     *
     * @mago-expect lint:excessive-parameter-list Fields are declared with named arguments; all but two are optional.
     */
    public function __construct(
        public string $name,
        public string $label,
        FieldType|string $type = FieldType::Text,
        public string $description = '',
        public ?string $setting = null,
        public bool $required = false,
        public string|int|float|bool|null $default = null,
        public array $options = [],
        public array $fields = [],
        public bool $transient = false,
        public bool $local = false,
        public array $attributes = [],
    ) {
        Names::field($name);
        $this->type = is_string($type) ? Names::text('A field type', $type) : $type->value;

        $names = [];
        foreach ($fields as $field) {
            if (array_key_exists($field->name, $names)) {
                throw InvalidDeclarationException::because(
                    sprintf('Field "%s" has two sub-fields named "%s"', $name, $field->name),
                );
            }
            $names[$field->name] = $field;
        }
    }

    /**
     * A password or secret, such as an API key: masked on screen, kept out of
     * logs, and stored in the site's .local.php.
     *
     * @throws InvalidDeclarationException When the name is malformed.
     */
    public static function password(
        string $name,
        string $label,
        string $description = '',
        ?string $setting = null,
        bool $required = false,
    ): self {
        return new self(
            name: $name,
            label: $label,
            type: FieldType::Password,
            description: $description,
            setting: $setting,
            required: $required,
            local: true,
        );
    }

    /**
     * Whether the host must mask the value on screen and in logs.
     */
    public function isMasked(): bool
    {
        return FieldType::Password->value === $this->type;
    }
}
