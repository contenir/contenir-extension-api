<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Extension;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Listener\ListenerInterface;
use Override;

final class SyncSubscriber implements ListenerInterface
{
    #[Override]
    public function handle(EventInterface $event, ExtensionContextInterface $context): void {}
}
