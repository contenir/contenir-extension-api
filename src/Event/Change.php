<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event;

use function array_diff_key;
use function array_key_exists;
use function array_keys;
use function in_array;

/**
 * Values before and after an action, and the fields whose values actually
 * changed. The host works out the change once per action, not once per
 * listener, which is what makes listening per field cheap.
 *
 * @api
 */
final readonly class Change
{
    /**
     * The fields whose values differ, including fields only one side has, in
     * the order of $after and then any $before alone has.
     *
     * @var list<string>
     */
    public array $changed;

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     *
     * @mago-expect analysis:mixed-assignment Field values are whatever the section holds; they are only compared.
     */
    public function __construct(
        public array $before,
        public array $after,
    ) {
        $changed = [];
        foreach ($after as $field => $value) {
            if (array_key_exists($field, $before) && $before[$field] === $value) {
                continue;
            }

            $changed[] = $field;
        }

        $this->changed = [...$changed, ...array_keys(array_diff_key($before, $after))];
    }

    /**
     * Whether any of $fields changed.
     */
    public function touches(string ...$fields): bool
    {
        foreach ($fields as $field) {
            if (in_array($field, $this->changed, strict: true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The same change with $field set to $value afterwards.
     */
    public function with(string $field, mixed $value): self
    {
        return new self($this->before, [...$this->after, $field => $value]);
    }
}
