<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Log;

use Contenir\Extension\Api\Handle;
use Override;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Stringable;

/**
 * A logger that stamps every entry with the add-on it came from, in the
 * context key "source" (extension.<handle>), so a host's log can tell an
 * add-on's entries from core's and filter by add-on. Hosts wrap their own
 * logger in it for each add-on's context; the stamp cannot be overridden by
 * the add-on.
 *
 * @api
 */
final class SourceLogger implements LoggerInterface
{
    use LoggerTrait;

    /**
     * The context key the add-on's log source is written under.
     */
    public const string KEY = 'source';

    public function __construct(
        private readonly LoggerInterface $inner,
        private readonly Handle $handle,
    ) {}

    /**
     * @param array<array-key, mixed> $context
     */
    #[Override]
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $this->inner->log($level, $message, [...$context, self::KEY => $this->handle->logSource()]);
    }
}
