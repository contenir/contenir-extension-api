<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Event\Catalogue;
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Event\PostEventInterface;
use Contenir\Extension\Api\Listener\ListenerInterface;
use Override;

/**
 * Writes the event's short name to the journal, with the outcome for a Post event.
 *
 * @api
 */
final class RecordStage implements ListenerInterface
{
    #[Override]
    public function handle(EventInterface $event, ExtensionContextInterface $context): void
    {
        $journal = Journal::from($context);
        $name    = Catalogue::name($event::class);
        $journal->write($event instanceof PostEventInterface ? "{$name}:{$event->outcome()->value}" : $name);
    }
}
