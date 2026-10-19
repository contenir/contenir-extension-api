<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Conformance\Probe\Event\Announced;
use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Listener\ListenerInterface;
use Override;

/**
 * Fires the probe's own event.
 *
 * @api
 */
final class Announce implements ListenerInterface
{
    #[Override]
    public function handle(EventInterface $event, ExtensionContextInterface $context): void
    {
        $journal = Journal::from($context);
        $context->events()->dispatch(new Announced('hello'));
        $journal->write('announced');
    }
}
