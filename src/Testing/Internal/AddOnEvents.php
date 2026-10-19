<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Event\CoreEventInterface;
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Exception\DispatchRefusedException;
use Override;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * The dispatcher add-ons fire their own events through. Core events are
 * refused, and the chain caps how deep events fired from listeners go.
 *
 * @internal
 */
final readonly class AddOnEvents implements EventDispatcherInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private Chain $chain,
    ) {}

    /**
     * @throws DispatchRefusedException When the event is a core event, not an event, or would recur or go too deep.
     * @throws ListenerFailedException  When a main-stage listener fails.
     */
    #[Override]
    public function dispatch(object $event): object
    {
        if ($event instanceof CoreEventInterface) {
            throw DispatchRefusedException::coreEvent($event::class);
        }

        if (! $event instanceof EventInterface) {
            throw DispatchRefusedException::notAnEvent($event::class);
        }

        $this->chain->enter($event::class);
        $failure = $this->dispatcher->dispatch(null, $event);
        $this->chain->leave();

        if (null !== $failure) {
            throw new ListenerFailedException($failure);
        }

        return $event;
    }
}
