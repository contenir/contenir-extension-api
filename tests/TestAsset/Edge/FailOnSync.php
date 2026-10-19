<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Edge;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Listener\ListenerInterface;
use Override;
use RuntimeException;

final class FailOnSync implements ListenerInterface
{
    /**
     * @throws RuntimeException Always.
     */
    #[Override]
    public function handle(EventInterface $event, ExtensionContextInterface $context): void
    {
        throw new RuntimeException('Sync listener failed');
    }
}
