<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Url\UrlResolverInterface;
use Override;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * The context the in-memory host gives an add-on's code.
 *
 * @api
 */
final readonly class InMemoryContext implements ExtensionContextInterface
{
    /**
     * @mago-expect lint:excessive-parameter-list One parameter per part of the context.
     */
    public function __construct(
        private Handle $handle,
        private ContainerInterface $services,
        private LoggerInterface $logger,
        private ClockInterface $clock,
        private string $sitePath,
        private UrlResolverInterface $urls,
        private EventDispatcherInterface $events,
    ) {}

    #[Override]
    public function clock(): ClockInterface
    {
        return $this->clock;
    }

    #[Override]
    public function events(): EventDispatcherInterface
    {
        return $this->events;
    }

    #[Override]
    public function handle(): Handle
    {
        return $this->handle;
    }

    #[Override]
    public function logger(): LoggerInterface
    {
        return $this->logger;
    }

    #[Override]
    public function services(): ContainerInterface
    {
        return $this->services;
    }

    #[Override]
    public function sitePath(): string
    {
        return $this->sitePath;
    }

    #[Override]
    public function urls(): UrlResolverInterface
    {
        return $this->urls;
    }
}
