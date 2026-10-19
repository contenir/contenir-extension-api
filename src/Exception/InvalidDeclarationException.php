<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Exception;

use InvalidArgumentException;

use function sprintf;
use function ucfirst;

/**
 * An add-on declared something the API cannot accept: a bad name, a class that
 * does not implement what its extension point needs, or a second declaration
 * under a name already taken. Thrown from register(), so a host skips the
 * add-on and reports why.
 *
 * @api
 */
final class InvalidDeclarationException extends InvalidArgumentException implements ExceptionInterface
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }

    public static function duplicate(string $kind, string $name): self
    {
        return new self(sprintf('%s "%s" is already declared', ucfirst($kind), $name));
    }

    public static function invalidName(string $kind, string $name, string $rule): self
    {
        return new self(sprintf('%s name "%s" must be %s', $kind, $name, $rule));
    }

    public static function missing(string $key): self
    {
        return new self(sprintf('The declaration has no "%s" string', $key));
    }

    /**
     * @param class-string $interface
     */
    public static function notImplementing(string $kind, string $class, string $interface): self
    {
        return new self(sprintf('%s class "%s" must implement %s', $kind, $class, $interface));
    }
}
