<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Edge;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Listener\ListenerInterface;
use Override;

final class CountingListener implements ListenerInterface
{
    public int $calls = 0;

    #[Override]
    public function handle(EventInterface $event, ExtensionContextInterface $context): void
    {
        ++$this->calls;
    }
}
