<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event;

use Override;

/**
 * The outcomes every Pre event offers its listeners.
 *
 * @api
 */
abstract class AbstractPreEvent implements PreEventInterface
{
    private ?string $stopMessage = null;

    /**
     * @var array<string, list<string>>
     */
    private array $errors = [];

    /**
     * @var array<string, list<string>>
     */
    private array $warnings = [];

    /**
     * @var list<string>
     */
    private array $flags = [];

    #[Override]
    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    /**
     * @return array<string, list<string>> Messages by field
     */
    public function errors(): array
    {
        return $this->errors;
    }

    #[Override]
    public function flag(string $reason): void
    {
        $this->flags[] = $reason;
    }

    /**
     * @return list<string>
     */
    public function flags(): array
    {
        return $this->flags;
    }

    #[Override]
    public function isPropagationStopped(): bool
    {
        return null !== $this->stopMessage;
    }

    #[Override]
    public function proceeds(): bool
    {
        return null === $this->stopMessage && [] === $this->errors;
    }

    #[Override]
    public function stop(string $message): void
    {
        $this->stopMessage = $message;
    }

    /**
     * The message the action was stopped with, or null when nothing stopped it.
     */
    public function stopMessage(): ?string
    {
        return $this->stopMessage;
    }

    #[Override]
    public function warn(string $field, string $message): void
    {
        $this->warnings[$field][] = $message;
    }

    /**
     * @return array<string, list<string>> Messages by field; '' holds notes about the whole subject
     */
    public function warnings(): array
    {
        return $this->warnings;
    }
}
