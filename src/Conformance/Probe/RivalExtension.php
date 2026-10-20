<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\ExtensionInterface;
use Contenir\Extension\Api\Form\Field;
use Contenir\Extension\Api\Form\Form;
use Contenir\Extension\Api\Registry;
use Contenir\Extension\Api\Settings\SettingsCategory;
use Contenir\Extension\Api\Settings\SettingsSection;
use Override;

/**
 * A second add-on providing the probe's NoteServiceInterface, as a second CDN
 * add-on would provide the same purger.
 *
 * @api
 */
final readonly class RivalExtension implements ExtensionInterface
{
    public const string HANDLE = 'rival';

    #[Override]
    public function register(Registry $registry): void
    {
        $registry->settingsSection(new SettingsSection(
            name: self::HANDLE,
            title: 'Rival',
            category: SettingsCategory::System,
            form: new Form([new Field('note', 'Note', default: 'rival note')]),
        ));
        $registry->provide(NoteServiceInterface::class, NoteServiceFactory::class);
    }
}
