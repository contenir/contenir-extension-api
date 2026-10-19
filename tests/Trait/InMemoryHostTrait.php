<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Trait;

use Contenir\Extension\Api\Conformance\HostDriverInterface;
use Contenir\Extension\Api\Testing\FrozenClock;
use Contenir\Extension\Api\Testing\InMemoryHost;
use DateTimeImmutable;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

trait InMemoryHostTrait
{
    protected function createHost(): HostDriverInterface
    {
        $factory = new Psr17Factory();

        return new InMemoryHost(
            $factory,
            $factory,
            new FrozenClock(new DateTimeImmutable('2026-10-19T09:00:00+00:00')),
        );
    }

    protected function requests(): ServerRequestFactoryInterface
    {
        return new Psr17Factory();
    }

    protected function streams(): StreamFactoryInterface
    {
        return new Psr17Factory();
    }
}
