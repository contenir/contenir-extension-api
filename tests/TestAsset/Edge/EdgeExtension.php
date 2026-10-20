<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Edge;

use Contenir\Extension\Api\Event\Extension\ExtensionPostFail;
use Contenir\Extension\Api\Event\Record\RecordPostSave;
use Contenir\Extension\Api\Event\Record\RecordPreSave;
use Contenir\Extension\Api\Event\Settings\SettingsPostSave;
use Contenir\Extension\Api\Event\Settings\SettingsPreSave;
use Contenir\Extension\Api\Event\Settings\SettingsSave;
use Contenir\Extension\Api\ExtensionInterface;
use Contenir\Extension\Api\Form\Field;
use Contenir\Extension\Api\Form\Form;
use Contenir\Extension\Api\Listener\Delivery;
use Contenir\Extension\Api\Registry;
use Contenir\Extension\Api\Settings\SettingsCategory;
use Contenir\Extension\Api\Settings\SettingsSection;
use Override;

final class EdgeExtension implements ExtensionInterface
{
    #[Override]
    public function register(Registry $registry): void
    {
        $registry->settingsSection(new SettingsSection(
            $registry->handle()->value,
            'Edge',
            SettingsCategory::System,
            new Form([
                new Field('mode', 'Mode'),
                new Field('size', 'Size', default: 'medium'),
            ]),
        ));
        $registry->listen('fail-settings', SettingsSave::class, FailOnSync::class);
        $registry->listen('stop', SettingsPreSave::class, StopAll::class);
        $registry->listen('stop', RecordPreSave::class, StopAll::class);
        $registry->listen('count', SettingsPostSave::class, CountingListener::class, delivery: Delivery::Immediate);
        $registry->listen('count', RecordPostSave::class, CountingListener::class, delivery: Delivery::Immediate);
        $registry->handler('redirect', RedirectHandler::class);
        $registry->handler('back', BackHandler::class);
        $registry->handler('screen', ScreenHandler::class);
        $registry->handler('bad-json', BadJsonHandler::class);
        $registry->handler('stray-template', StrayTemplateHandler::class);
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
