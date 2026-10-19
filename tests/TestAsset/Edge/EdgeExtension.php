<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Edge;

use Contenir\Extension\Api\Event\Extension\ExtensionPostFail;
use Contenir\Extension\Api\Event\Record\RecordPreSave;
use Contenir\Extension\Api\ExtensionInterface;
use Contenir\Extension\Api\Registry;
use Override;

final class EdgeExtension implements ExtensionInterface
{
    #[Override]
    public function register(Registry $registry): void
    {
        $registry->handler('redirect', RedirectHandler::class);
        $registry->handler('back', BackHandler::class);
        $registry->handler('screen', ScreenHandler::class);
        $registry->handler('bad-json', BadJsonHandler::class);
        $registry->webhook('explode', ExplodingWebhook::class);
        $registry->diagnostic('explode', ExplodingCheck::class);
        $registry->diagnostic('broken-fix', BrokenFixCheck::class);
        $registry->diagnostic('explain', ExplainCheck::class);
        $registry->listen('fail-on-sync', Synced::class, FailOnSync::class);
        $registry->listen('count', RecordPreSave::class, CountingListener::class);
        $registry->listen('count', Synced::class, CountingListener::class);
        $registry->listen('fail-on-failure', ExtensionPostFail::class, FailOnSync::class);
    }
}
