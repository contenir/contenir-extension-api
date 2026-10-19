<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Diagnostic;

/**
 * Whether a fix did what it set out to, with a message for the person who
 * applied it.
 *
 * @api
 */
final readonly class FixResult
{
    private function __construct(
        public bool $applied,
        public string $message,
    ) {}

    public static function applied(string $message = ''): self
    {
        return new self(true, $message);
    }

    public static function failed(string $message): self
    {
        return new self(false, $message);
    }
}
