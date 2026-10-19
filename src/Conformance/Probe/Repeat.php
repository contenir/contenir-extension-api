<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Conformance\Probe\Event\Announced;
use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Exception\DispatchRefusedException;
use Contenir\Extension\Api\Listener\ListenerInterface;
use Override;

/**
 * Hears the probe's own event and fires it again, which the host must refuse as a recurrence.
 *
 * @api
 */
final class Repeat implements ListenerInterface
{
    #[Override]
    public function handle(EventInterface $event, ExtensionContextInterface $context): void
    {
        $journal = Journal::from($context);
        $journal->write('echo');
        try {
            $context->events()->dispatch(new Announced('again'));
            $journal->write('echoed');
        } catch (DispatchRefusedException) {
            $journal->write('refused:recurrence');
        }
    }
}
