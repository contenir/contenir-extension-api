<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Closure;
use Contenir\Extension\Api\Editor\Editor;
use Contenir\Extension\Api\Event\Extension\FailurePoint;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Queue\QueuedTaskInterface;
use Contenir\Extension\Api\Queue\TaskDeclaration;
use Contenir\Extension\Api\Queue\TaskQueueInterface;
use Contenir\Extension\Api\Registration;
use Override;
use Throwable;

use function array_shift;
use function json_encode;
use function sprintf;

/**
 * Queued tasks held until a test runs them, each as the editor who queued it, as the host's job runner does.
 *
 * @internal
 */
final class QueuedTasks implements TaskQueueInterface
{
    /**
     * @var list<array{handle: string, task: TaskDeclaration, payload: array<string, mixed>, grants: list<string>|null, editor: ?Editor}>
     */
    private array $pending = [];

    /**
     * @param Closure(string): Registration $registration
     */
    public function __construct(
        private readonly Runtime $runtime,
        private readonly SignedIn $signedIn,
        private readonly Closure $registration,
    ) {}

    #[Override]
    public function enqueue(string $handle, string $task, array $payload = []): void
    {
        $declaration = ($this->registration)($handle)->tasks[$task] ?? throw InvalidDeclarationException::because(
            sprintf('Add-on "%s" declared no task "%s"', $handle, $task),
        );

        if (false === json_encode($payload)) {
            throw InvalidDeclarationException::because('A queued task\'s payload must be JSON-encodable');
        }

        $this->pending[] = [
            'handle'  => $handle,
            'task'    => $declaration,
            'payload' => $payload,
            'grants'  => $this->signedIn->grants(),
            'editor'  => $this->signedIn->current(),
        ];
    }

    /**
     * Runs what is queued, in order, including what the tasks queue; a task that throws is reported as the
     * add-on's failure and not tried again.
     *
     * @return int How many tasks ran
     */
    public function run(): int
    {
        $ran = 0;
        while (null !== ($queued = array_shift($this->pending))) {
            ++$ran;
            if (null !== $queued['grants']) {
                $this->signedIn->signIn($queued['grants'], $queued['editor']);
            }

            try {
                $this->runtime
                    ->make($queued['task']->class, QueuedTaskInterface::class)
                    ->run($queued['payload'], $this->runtime->context(new Handle($queued['handle'])));
            } catch (Throwable $failure) {
                $this->runtime->report(
                    new Handle($queued['handle']),
                    FailurePoint::Task,
                    $queued['task']->name,
                    $failure,
                );
            } finally {
                $this->signedIn->signOut();
            }
        }

        return $ran;
    }
}
