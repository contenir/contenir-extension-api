<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Listener\ListenerInterface;
use Override;

/**
 * Writes "b" to the journal, so the order listeners ran in can be read back.
 *
 * @api
 */
final class OrderB implements ListenerInterface
{
    #[Override]
    public function handle(EventInterface $event, ExtensionContextInterface $context): void
    {
        $journal = Journal::from($context);
        $journal->write('b');
    }
}
