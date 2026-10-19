<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Listener\ListenerInterface;
use Override;
use RuntimeException;

/**
 * Throws, wherever it is bound.
 *
 * @api
 */
final class Fail implements ListenerInterface
{
    /**
     * @throws RuntimeException Always.
     */
    #[Override]
    public function handle(EventInterface $event, ExtensionContextInterface $context): void
    {
        throw new RuntimeException('The probe failed on purpose');
    }
}
