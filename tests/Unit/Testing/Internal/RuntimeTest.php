<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Testing\Internal;

use Contenir\Extension\Api\Listener\ListenerInterface;
use Contenir\Extension\Api\Settings\SettingsInterface;
use Contenir\Extension\Api\Testing\FrozenClock;
use Contenir\Extension\Api\Testing\Internal\Runtime;
use Contenir\Extension\Api\Testing\MapUrlResolver;
use Contenir\Extension\Api\Testing\MemoryLogger;
use Contenir\Extension\Api\Testing\ServiceMap;
use Contenir\Extension\Api\Tests\TestAsset\Edge\BrokenContainer;
use Contenir\Extension\Api\Tests\TestAsset\Extension\ResyncTask;
use Contenir\Extension\Api\Tests\TestAsset\Extension\SyncSubscriber;
use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

#[Group('unit')]
final class RuntimeTest extends TestCase
{
    #[Test]
    public function constructsAClassNoServiceProvides(): void
    {
        static::assertInstanceOf(SyncSubscriber::class, $this->runtime(new ServiceMap())->make(
            SyncSubscriber::class,
            ListenerInterface::class,
        ));
    }

    #[Test]
    public function refusesAClassOfTheWrongKind(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(ResyncTask::class . ' is not a ' . ListenerInterface::class);

        $this->runtime(new ServiceMap())->make(ResyncTask::class, ListenerInterface::class);
    }

    #[Test]
    public function refusesAServiceOfTheWrongKind(): void
    {
        $this->expectException(LogicException::class);

        $this->runtime(new ServiceMap([SyncSubscriber::class => new ResyncTask()]))->make(
            SyncSubscriber::class,
            ListenerInterface::class,
        );
    }

    #[Test]
    public function saysWhenTheContainerFails(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Could not make ' . SyncSubscriber::class);

        $this->runtime(new BrokenContainer())->make(SyncSubscriber::class, ListenerInterface::class);
    }

    private function runtime(ContainerInterface $services): Runtime
    {
        return new Runtime(
            $services,
            new MemoryLogger(),
            new FrozenClock(new DateTimeImmutable('2026-10-19T09:00:00+00:00')),
            '/site',
            new MapUrlResolver(),
            fn(): EventDispatcherInterface => $this->createStub(EventDispatcherInterface::class),
            static function (): void {},
            fn(): SettingsInterface => $this->createStub(SettingsInterface::class),
        );
    }
}
