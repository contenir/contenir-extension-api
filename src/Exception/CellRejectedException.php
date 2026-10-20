<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Exception;

use InvalidArgumentException;

/**
 * A CSV cell an importable field type can't turn into a value, with a
 * message the host shows the editor against the cell.
 *
 * @api
 */
final class CellRejectedException extends InvalidArgumentException implements ExceptionInterface
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
