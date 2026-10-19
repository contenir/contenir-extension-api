<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Listener\ListenerInterface;
use Override;

/**
 * Writes a log entry through the add-on's logger.
 *
 * @api
 */
final class LogVisit implements ListenerInterface
{
    #[Override]
    public function handle(EventInterface $event, ExtensionContextInterface $context): void
    {
        $context->logger()->info('The probe was here', ['event' => $event::class]);
    }
}
