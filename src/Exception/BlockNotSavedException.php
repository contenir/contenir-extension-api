<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Exception;

use RuntimeException;

/**
 * The host would not save an add-on's section block: it does not save here, the site's configuration
 * changed since the add-on read it, or the result would not load.
 *
 * @api
 */
final class BlockNotSavedException extends RuntimeException implements ExceptionInterface
{
    public const string NOT_ALLOWED = 'not-allowed';

    public const string CHANGED = 'changed';

    public const string REFUSED = 'refused';

    private function __construct(
        string $message,
        public readonly string $reason,
    ) {
        parent::__construct($message);
    }

    public static function changed(): self
    {
        return new self('The site\'s configuration has changed since it was read', self::CHANGED);
    }

    public static function notAllowed(): self
    {
        return new self('This site does not save its configuration from here', self::NOT_ALLOWED);
    }

    public static function refused(string $because): self
    {
        return new self($because, self::REFUSED);
    }
}
