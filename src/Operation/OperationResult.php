<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Operation;

use Override;

/**
 * Immutable OperationResultInterface implementation. Build with the static
 * factories and chain withMessage(); $level mirrors the host messenger
 * vocabulary (success|warning|danger|error|info).
 *
 * @api
 */
final readonly class OperationResult implements OperationResultInterface
{
    /**
     * @param array<string, mixed>|null                $redirect
     * @param list<array{level: string, text: string}> $messages
     */
    private function __construct(
        private ?array $redirect,
        private array $messages,
    ) {}

    public static function message(string $level, string $text): self
    {
        return new self(null, [['level' => $level, 'text' => $text]]);
    }

    /**
     * @param array<string, mixed> $routeParams
     */
    public static function redirectTo(array $routeParams): self
    {
        return new self($routeParams, []);
    }

    #[Override]
    public function messages(): array
    {
        return $this->messages;
    }

    #[Override]
    public function redirect(): ?array
    {
        return $this->redirect;
    }

    public function withMessage(string $level, string $text): self
    {
        return new self($this->redirect, [...$this->messages, ['level' => $level, 'text' => $text]]);
    }
}
