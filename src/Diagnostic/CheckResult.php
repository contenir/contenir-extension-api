<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Diagnostic;

/**
 * The outcome of a check, with the fix to offer when it can be put right.
 *
 * @api
 */
final readonly class CheckResult
{
    private function __construct(
        public Status $status,
        public string $message,
        public OneClickFix|GuidedFix|null $fix,
    ) {}

    public static function failure(string $message, OneClickFix|GuidedFix|null $fix = null): self
    {
        return new self(Status::Failure, $message, $fix);
    }

    public static function skipped(string $message): self
    {
        return new self(Status::Skipped, $message, null);
    }

    public static function success(string $message = ''): self
    {
        return new self(Status::Success, $message, null);
    }

    public static function warning(string $message, OneClickFix|GuidedFix|null $fix = null): self
    {
        return new self(Status::Warning, $message, $fix);
    }
}
