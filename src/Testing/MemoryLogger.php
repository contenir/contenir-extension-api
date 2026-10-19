<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing;

use Override;
use Psr\Log\AbstractLogger;
use Stringable;

use function get_debug_type;
use function is_string;

/**
 * A PSR-3 logger that keeps every entry, for tests to read back.
 *
 * @api
 */
final class MemoryLogger extends AbstractLogger
{
    /**
     * @var list<array{level: string, message: string, context: array<array-key, mixed>}>
     */
    private array $entries = [];

    /**
     * @return list<array{level: string, message: string, context: array<array-key, mixed>}>
     */
    public function entries(): array
    {
        return $this->entries;
    }

    /**
     * @param array<array-key, mixed> $context
     */
    #[Override]
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $this->entries[] = [
            'level'   => is_string($level) ? $level : get_debug_type($level),
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}
