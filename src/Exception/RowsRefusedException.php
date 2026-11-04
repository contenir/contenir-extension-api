<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Exception;

use InvalidArgumentException;

use function sprintf;

/**
 * An add-on asked for record values its section blocks don't give it: a
 * section without its block, or a column its block doesn't read; or asked
 * with a negative limit or offset.
 *
 * @api
 */
final class RowsRefusedException extends InvalidArgumentException implements ExceptionInterface
{
    public static function column(string $section, string $column): self
    {
        return new self(sprintf('The block on %s does not read column "%s"', $section, $column));
    }

    public static function range(int $limit, int $offset): self
    {
        return new self(sprintf('Rows need a limit and offset of 0 or more, not %d and %d', $limit, $offset));
    }

    public static function section(string $handle, string $section): self
    {
        return new self(sprintf('Section %s has no block of add-on "%s"', $section, $handle));
    }
}
