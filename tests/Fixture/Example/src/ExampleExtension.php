<?php

declare(strict_types=1);

namespace Contenir\Extension\Example;

use Contenir\Extension\Api\Event\Record\RecordPostSave;
use Contenir\Extension\Api\ExtensionInterface;
use Contenir\Extension\Api\Form\Field;
use Contenir\Extension\Api\Form\FieldType;
use Contenir\Extension\Api\Form\Form;
use Contenir\Extension\Api\Listener\Scope;
use Contenir\Extension\Api\Registry;
use Contenir\Extension\Api\Settings\SettingsCategory;
use Contenir\Extension\Api\Settings\SettingsSection;
use Override;

/**
 * The example add-on: a settings section holding an endpoint and a signing
 * secret, a listener that logs title changes, a webhook that answers signed
 * pings, and a check that the secret is set.
 *
 * @api
 */
final class ExampleExtension implements ExtensionInterface
{
    #[Override]
    public function register(Registry $registry): void
    {
        $registry->settingsSection(new SettingsSection(
            name: 'example',
            title: 'Example',
            category: SettingsCategory::Integration,
            form: new Form([
                new Field('endpoint', 'Endpoint', FieldType::Url, 'Where pings come from'),
                Field::password('secret', 'Signing secret', 'Shared with the service that sends pings'),
            ]),
        ));

        $registry->listen('note-title', RecordPostSave::class, NoteTitle::class, Scope::fields('title'));
        $registry->webhook('ping', Ping::class);
        $registry->diagnostic('secret', SecretCheck::class);
    }
}
