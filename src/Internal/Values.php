<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Internal;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;

use function is_bool;
use function is_string;

/**
 * Typed reads from the plain arrays a host round-trips declarations through.
 *
 * @internal
 */
final class Values
{
    /**
     * The boolean at $key, or $default when it is missing or not a boolean.
     *
     * @param array<string, mixed> $data
     *
     * @mago-expect analysis:mixed-assignment The array is untyped host input; the type is checked here.
     */
    public static function bool(array $data, string $key, bool $default = false): bool
    {
        $value = $data[$key] ?? null;

        return is_bool($value) ? $value : $default;
    }

    /**
     * The string at $key, or null when it is missing or not a string.
     *
     * @param array<string, mixed> $data
     *
     * @mago-expect analysis:mixed-assignment The array is untyped host input; the type is checked here.
     */
    public static function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * The string at $key, failing when it is missing or not a string.
     *
     * @param array<string, mixed> $data
     *
     * @mago-expect analysis:mixed-assignment The array is untyped host input; the type is checked here.
     * @throws InvalidDeclarationException
     */
    public static function required(array $data, string $key): string
    {
        $value = $data[$key] ?? null;
        if (! is_string($value)) {
            throw InvalidDeclarationException::missing($key);
        }

        return $value;
    }

    /**
     * The string at $key, or $default when it is missing or not a string.
     *
     * @param array<string, mixed> $data
     *
     * @mago-expect analysis:mixed-assignment The array is untyped host input; the type is checked here.
     */
    public static function string(array $data, string $key, string $default = ''): string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : $default;
    }
}
