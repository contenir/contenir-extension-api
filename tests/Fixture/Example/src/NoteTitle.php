<?php

declare(strict_types=1);

namespace Contenir\Extension\Example;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Event\Record\RecordPostSave;
use Contenir\Extension\Api\Listener\ListenerInterface;
use Override;

/**
 * Logs a record's new title once its save has gone through.
 *
 * @api
 */
final class NoteTitle implements ListenerInterface
{
    #[Override]
    public function handle(EventInterface $event, ExtensionContextInterface $context): void
    {
        if (! $event instanceof RecordPostSave || ! $event->succeeded()) {
            return;
        }

        $context->logger()->info('A title changed', [
            'section' => $event->section,
            'id'      => $event->id,
            'title'   => $event->change()->after['title'] ?? null,
        ]);
    }
}
