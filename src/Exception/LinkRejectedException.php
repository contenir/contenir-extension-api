<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Exception;

use InvalidArgumentException;

/**
 * A posted link field the editor left incomplete, such as Create new
 * without a title, with a message the add-on shows them against the form.
 *
 * @api
 */
final class LinkRejectedException extends InvalidArgumentException implements ExceptionInterface
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
