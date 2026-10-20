<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Internal;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;

use function is_a;
use function preg_match;
use function trim;

/**
 * The naming and class rules declarations share.
 *
 * @internal
 */
final class Names
{
    /**
     * Names of handlers, operations, checks, schedules and the like: they
     * appear in URLs and permission names.
     */
    private const string NAME = '/^[a-z][a-z0-9-]{0,63}$/D';

    /**
     * Field names, which become setting keys and database columns.
     */
    private const string FIELD = '/^[a-z][a-z0-9_]{0,63}$/D';

    /**
     * A module template's path within its templates folder, without the
     * extension: names joined by slashes.
     */
    private const string TEMPLATE = '#^[a-z][a-z0-9-]{0,63}(/[a-z][a-z0-9-]{0,63}){0,7}$#D';

    /**
     * A PHP namespace, without leading or trailing backslashes.
     */
    private const string NAMESPACE = '/^[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*$/D';

    /**
     * @throws InvalidDeclarationException When $name breaks the rule.
     */
    public static function field(string $name): string
    {
        if (1 !== preg_match(self::FIELD, $name)) {
            throw InvalidDeclarationException::invalidName(
                'Field',
                $name,
                '1 to 64 lowercase letters, digits and underscores, starting with a letter',
            );
        }

        return $name;
    }

    /**
     * $class, failing unless it is a class implementing $interface.
     *
     * @template T of object
     *
     * @param class-string<T> $interface
     *
     * @return class-string<T>
     *
     * @throws InvalidDeclarationException
     */
    public static function implementing(string $kind, string $class, string $interface): string
    {
        if (! is_a($class, $interface, allow_string: true)) {
            throw InvalidDeclarationException::notImplementing($kind, $class, $interface);
        }

        return $class;
    }

    /**
     * @throws InvalidDeclarationException When $name breaks the rule.
     */
    public static function name(string $kind, string $name): string
    {
        if (1 !== preg_match(self::NAME, $name)) {
            throw InvalidDeclarationException::invalidName(
                $kind,
                $name,
                '1 to 64 lowercase letters, digits and dashes, starting with a letter',
            );
        }

        return $name;
    }

    /**
     * @throws InvalidDeclarationException When $namespace is not a PHP namespace.
     */
    public static function namespace(string $namespace): string
    {
        if (1 !== preg_match(self::NAMESPACE, $namespace)) {
            throw InvalidDeclarationException::invalidName('Namespace', $namespace, 'a PHP namespace');
        }

        return $namespace;
    }

    /**
     * @throws InvalidDeclarationException When $name is not a template path.
     */
    public static function template(string $name): string
    {
        if (1 !== preg_match(self::TEMPLATE, $name)) {
            throw InvalidDeclarationException::invalidName(
                'Template',
                $name,
                'names of lowercase letters, digits and dashes, joined by slashes, without an extension',
            );
        }

        return $name;
    }

    /**
     * $value, failing when it is empty or only whitespace.
     *
     * @throws InvalidDeclarationException
     */
    public static function text(string $what, string $value): string
    {
        if ('' === trim($value)) {
            throw InvalidDeclarationException::because("{$what} must not be empty");
        }

        return $value;
    }
}
