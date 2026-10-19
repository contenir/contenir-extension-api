<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Closure;
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Event\Extension\ExtensionPostFail;
use Contenir\Extension\Api\Event\Extension\FailurePoint;
use Contenir\Extension\Api\Event\PostEventInterface;
use Contenir\Extension\Api\Event\PreEventInterface;
use Contenir\Extension\Api\Listener\Delivery;
use Contenir\Extension\Api\Listener\ListenerInterface;
use Throwable;

use function array_shift;
use function sprintf;

/**
 * Runs the listeners bound to an event with the meaning each stage has.
 * At Pre they run in order on the same event until one stops it, and a
 * failing listener stops it. At the main stage a failing listener fails
 * the action. At Post a failure is only reported, and listeners not asking
 * to run immediately wait in the queue for the job runner.
 *
 * @internal
 */
final class Dispatcher
{
    /**
     * @var list<Closure(): void>
     */
    private array $queue = [];

    public function __construct(
        private readonly Bindings $bindings,
        private readonly Runtime $runtime,
    ) {}

    /**
     * Runs the listeners bound to $event, returning the summary of a
     * main-stage listener's failure, which ends the run, or null.
     */
    public function dispatch(?string $section, EventInterface $event): ?string
    {
        foreach ($this->bindings->for($section, $event::class) as $bound) {
            if (! $bound->scope->matches($event)) {
                continue;
            }

            if ($event instanceof PostEventInterface) {
                $this->post($bound, $event);
                continue;
            }

            $failure = $this->call($bound, $event);
            if ($event instanceof PreEventInterface) {
                if (null !== $failure) {
                    $event->stop(
                        sprintf('The %s add-on couldn\'t check this, so it didn\'t go ahead', $bound->handle->value),
                        $failure,
                    );
                }

                if ($event->isPropagationStopped()) {
                    return null;
                }

                continue;
            }

            if (null !== $failure) {
                return $failure;
            }
        }

        return null;
    }

    /**
     * Runs queued listeners until none are left, including any queued while
     * it runs.
     */
    public function drain(): void
    {
        while ([] !== $this->queue) {
            $job = array_shift($this->queue);
            $job();
        }
    }

    /**
     * Runs one listener, returning its failure's summary when it throws.
     */
    private function call(BoundListener $bound, EventInterface $event): ?string
    {
        try {
            $this->runtime
                ->make($bound->declaration->listener, ListenerInterface::class)
                ->handle($event, $this->runtime->context($bound->handle));
        } catch (Throwable $failure) {
            $name = $bound->declaration->name;

            return $event instanceof ExtensionPostFail
                ? $this->runtime->record($bound->handle, FailurePoint::Listener, $name, $failure)->reason()
                : $this->runtime->report($bound->handle, FailurePoint::Listener, $name, $failure);
        }

        return null;
    }

    private function post(BoundListener $bound, PostEventInterface $event): void
    {
        if (Delivery::Immediate === $bound->declaration->delivery) {
            $this->call($bound, $event);

            return;
        }

        $this->queue[] = function () use ($bound, $event): void {
            $this->call($bound, $event);
        };
    }
}
