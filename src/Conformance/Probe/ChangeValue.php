<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Event\AbstractChangePreEvent;
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Listener\ListenerInterface;
use Override;

use function is_string;
use function str_replace;
use function strtolower;

/**
 * Sets slug from title before a save is written.
 *
 * @api
 */
final class ChangeValue implements ListenerInterface
{
    #[Override]
    public function handle(EventInterface $event, ExtensionContextInterface $context): void
    {
        if (! $event instanceof AbstractChangePreEvent) {
            return;
        }

        $values = $event->values();
        if (! is_string($values['title'] ?? null)) {
            return;
        }

        $event->setValue(
            'slug',
            strtolower(str_replace(
                search: ' ',
                replace: '-',
                subject: $values['title'],
            )),
        );
    }
}
