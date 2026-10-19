<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Event\PreEventInterface;
use Contenir\Extension\Api\Listener\ListenerInterface;
use Override;

/**
 * Lets the action go ahead with a warning and a flag.
 *
 * @api
 */
final class WarnAndFlag implements ListenerInterface
{
    #[Override]
    public function handle(EventInterface $event, ExtensionContextInterface $context): void
    {
        if ($event instanceof PreEventInterface) {
            $event->warn('title', 'The probe noticed the title');
            $event->flag('probed');
        }
    }
}
