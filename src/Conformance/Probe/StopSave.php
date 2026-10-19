<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Event\PreEventInterface;
use Contenir\Extension\Api\Listener\ListenerInterface;
use Override;

/**
 * Stops the action it listens to.
 *
 * @api
 */
final class StopSave implements ListenerInterface
{
    #[Override]
    public function handle(EventInterface $event, ExtensionContextInterface $context): void
    {
        if ($event instanceof PreEventInterface) {
            $event->stop('Stopped by the probe');
        }
    }
}
