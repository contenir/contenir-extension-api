<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Exception;

use InvalidArgumentException;

use function sprintf;

/**
 * An add-on asked for a settings section or field it did not declare.
 *
 * @api
 */
final class UnknownSettingException extends InvalidArgumentException implements ExceptionInterface
{
    public static function field(string $section, string $field): self
    {
        return new self(sprintf('Settings section "%s" has no field "%s"', $section, $field));
    }

    public static function section(string $handle, string $section): self
    {
        return new self(sprintf('Add-on "%s" has no settings section "%s"', $handle, $section));
    }
}
