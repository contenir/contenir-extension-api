<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Exception;

use RuntimeException;

use function sprintf;

/**
 * A host's dispatcher refused an event an add-on tried to fire: a core
 * event, which only the CMS fires; an event already running in the same
 * chain; or a chain grown too deep.
 *
 * @api
 */
final class DispatchRefusedException extends RuntimeException implements ExceptionInterface
{
    public static function coreEvent(string $event): self
    {
        return new self(sprintf('%s is a core event, which only the CMS fires', $event));
    }

    public static function notAnEvent(string $event): self
    {
        return new self(sprintf('%s is not an event', $event));
    }

    public static function recurrence(string $event): self
    {
        return new self(sprintf('%s is already running in this chain', $event));
    }

    public static function tooDeep(string $event, int $depth): self
    {
        return new self(sprintf('%s would make the chain %d events deep', $event, $depth));
    }
}
