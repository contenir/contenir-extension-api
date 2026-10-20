<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Operation;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Everything an operation handler may need from the host, expressed without any
 * framework-specific types so a handler is portable across ZF1, Laminas and
 * Mezzio hosts.
 *
 * @api
 */
interface OperationContextInterface
{
    /**
     * @return array<string, mixed>
     */
    public function allPost(): array;

    /**
     * The context of the add-on whose settings section declared the
     * operation, for its settings and its logger: null for a core section's
     * operation. logger() is that add-on's logger too.
     */
    public function extension(): ?ExtensionContextInterface;

    public function isPost(): bool;

    public function logger(): LoggerInterface;

    public function param(string $name, mixed $default = null): mixed;

    public function post(string $name, mixed $default = null): mixed;

    public function section(): SectionViewInterface;

    public function services(): ContainerInterface;

    public function sitePath(): string;
}
