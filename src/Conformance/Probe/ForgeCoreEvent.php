<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Event\Change;
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Event\Record\RecordPostSave;
use Contenir\Extension\Api\Exception\DispatchRefusedException;
use Contenir\Extension\Api\Listener\ListenerInterface;
use Override;

/**
 * Tries to fire a core event, which the host must refuse.
 *
 * @api
 */
final class ForgeCoreEvent implements ListenerInterface
{
    #[Override]
    public function handle(EventInterface $event, ExtensionContextInterface $context): void
    {
        $journal = Journal::from($context);
        try {
            $context->events()->dispatch(new RecordPostSave('forged', 1, new Change([], [])));
            $journal->write('forged');
        } catch (DispatchRefusedException) {
            $journal->write('refused:core');
        }
    }
}
