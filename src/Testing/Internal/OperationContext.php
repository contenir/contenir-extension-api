<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Operation\OperationContextInterface;
use Contenir\Extension\Api\Operation\SectionViewInterface;
use Override;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use function array_key_exists;

/**
 * What an operation on an add-on's settings section runs with: the posted
 * values, the section, and the add-on's context, logger and services.
 *
 * @internal
 */
final readonly class OperationContext implements OperationContextInterface
{
    /**
     * @param array<string, mixed> $posted
     */
    public function __construct(
        private array $posted,
        private SectionViewInterface $section,
        private ExtensionContextInterface $context,
    ) {}

    #[Override]
    public function allPost(): array
    {
        return $this->posted;
    }

    #[Override]
    public function extension(): ExtensionContextInterface
    {
        return $this->context;
    }

    #[Override]
    public function isPost(): bool
    {
        return true;
    }

    #[Override]
    public function logger(): LoggerInterface
    {
        return $this->context->logger();
    }

    #[Override]
    public function param(string $name, mixed $default = null): mixed
    {
        return $this->post($name, $default);
    }

    #[Override]
    public function post(string $name, mixed $default = null): mixed
    {
        return array_key_exists($name, $this->posted) ? $this->posted[$name] : $default;
    }

    #[Override]
    public function section(): SectionViewInterface
    {
        return $this->section;
    }

    #[Override]
    public function services(): ContainerInterface
    {
        return $this->context->services();
    }

    #[Override]
    public function sitePath(): string
    {
        return $this->context->sitePath();
    }
}
