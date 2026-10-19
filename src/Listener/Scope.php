<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Listener;

use Contenir\Extension\Api\Event\ChangeEventInterface;
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

use function array_unique;
use function array_values;
use function in_array;
use function sprintf;

/**
 * The fields a listener cares about. With a field scope it runs only when one
 * of those fields changed, so an alt-text tool bound to image stays quiet
 * when someone edits the title. The add-on declares the widest scope; a
 * site's binding may narrow it but never widen it.
 *
 * @api
 */
final readonly class Scope
{
    /**
     * @param list<string>|null $fields Null for every change
     */
    private function __construct(
        public ?array $fields,
    ) {}

    /**
     * Every change.
     */
    public static function all(): self
    {
        return new self(null);
    }

    /**
     * Only changes to one of these fields.
     *
     * @throws InvalidDeclarationException When a field name is malformed.
     */
    public static function fields(string $field, string ...$more): self
    {
        $fields = [$field, ...$more];
        foreach ($fields as $name) {
            Names::field($name);
        }

        return new self(array_values(array_unique($fields)));
    }

    /**
     * Whether a listener with this scope runs for $event.
     */
    public function matches(EventInterface $event): bool
    {
        if (null === $this->fields) {
            return true;
        }

        return $event instanceof ChangeEventInterface && $event->change()->touches(...$this->fields);
    }

    /**
     * The scope a site's binding asks for, which must stay within this one.
     * No fields keeps this scope as it is.
     *
     * @throws InvalidDeclarationException When a field is outside this scope, or malformed.
     */
    public function narrow(string ...$fields): self
    {
        if ([] === $fields) {
            return $this;
        }

        foreach ($fields as $field) {
            if (null !== $this->fields && ! in_array($field, $this->fields, strict: true)) {
                throw InvalidDeclarationException::because(
                    sprintf('Field "%s" is outside the listener\'s scope, which a binding cannot widen', $field),
                );
            }
        }

        return self::fields(...$fields);
    }
}
