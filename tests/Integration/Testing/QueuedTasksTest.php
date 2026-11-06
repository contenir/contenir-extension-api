<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Integration\Testing;

use Contenir\Extension\Api\Editor\Editor;
use Contenir\Extension\Api\Event\Extension\FailurePoint;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\ExtensionInterface;
use Contenir\Extension\Api\Queue\TaskQueueInterface;
use Contenir\Extension\Api\Registry;
use Contenir\Extension\Api\Testing\FrozenClock;
use Contenir\Extension\Api\Testing\InMemoryHost;
use Contenir\Extension\Api\Tests\TestAsset\Extension\QueueingHandler;
use Contenir\Extension\Api\Tests\TestAsset\Extension\RecordingTask;
use DateTimeImmutable;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_filter;
use function array_key_last;
use function array_map;
use function array_values;
use function str_starts_with;

use const NAN;

#[Group('integration')]
final class QueuedTasksTest extends TestCase
{
    private Psr17Factory $http;

    private InMemoryHost $host;

    #[Test]
    public function rejectsAPayloadThatCannotBeEncoded(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        $this->queue()->enqueue('queued', 'record', ['nan' => NAN]);
    }

    #[Test]
    public function rejectsATaskTheAddOnDidNotDeclare(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Add-on "queued" declared no task "missing"');

        $this->queue()->enqueue('queued', 'missing');
    }

    #[Test]
    public function reportsATaskThatFailsAndGoesOnToTheNext(): void
    {
        $this->queue()->enqueue('queued', 'record', ['fail' => true]);
        $this->queue()->enqueue('queued', 'record', ['n' => 2]);
        $this->host->runQueued();

        static::assertSame(
            [[FailurePoint::Task, 'record'], 1],
            [
                array_values(array_map(
                    static fn($failure): array => [$failure->point, $failure->name],
                    $this->host->failures(),
                ))[0] ?? null,
                $this->ran(),
            ],
        );
    }

    #[Test]
    public function runsAQueuedTaskAsTheEditorWhoQueuedIt(): void
    {
        $this->host->handle(
            $this->http->createServerRequest('GET', '/admin/extension/queued/queue'),
            ['extension.queued.queue'],
            new Editor(7, 'Ann Editor'),
        );

        static::assertSame([0, 1, 'ran [{"n":1},"Ann Editor"]'], [
            $this->ran(),
            $this->host->runQueued(),
            $this->lastRun(),
        ]);
    }

    #[Test]
    public function runsNothingUntilAskedTo(): void
    {
        $this->queue()->enqueue('queued', 'record');

        static::assertSame(0, $this->ran());
    }

    #[Override]
    protected function setUp(): void
    {
        $this->http = new Psr17Factory();
        $this->host = new InMemoryHost($this->http, $this->http, new FrozenClock(new DateTimeImmutable('2026-11-06')));
        $this->host->boot(
            ['queued' => new class implements ExtensionInterface {
                #[Override]
                public function register(Registry $registry): void
                {
                    $registry->task('record', RecordingTask::class);
                    $registry->handler('queue', QueueingHandler::class);
                }
            }],
            ['queued'],
        );
    }

    private function lastRun(): ?string
    {
        $messages = array_map(static fn(array $entry): string => (string) $entry['message'], $this->host->logs());
        $ran      = array_values(array_filter($messages, static fn(string $m): bool => str_starts_with($m, 'ran ')));

        return $ran[array_key_last($ran)] ?? null;
    }

    private function queue(): TaskQueueInterface
    {
        $queue = $this->host->context('queued')->services()->get(TaskQueueInterface::class);
        static::assertInstanceOf(TaskQueueInterface::class, $queue);

        return $queue;
    }

    private function ran(): int
    {
        return null === $this->lastRun() ? 0 : 1;
    }
}
