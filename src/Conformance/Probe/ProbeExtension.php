<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Conformance\Probe\Event\Announced;
use Contenir\Extension\Api\Event\Record\RecordCreate;
use Contenir\Extension\Api\Event\Record\RecordPostCreate;
use Contenir\Extension\Api\Event\Record\RecordPostSave;
use Contenir\Extension\Api\Event\Record\RecordPreCreate;
use Contenir\Extension\Api\Event\Record\RecordPreSave;
use Contenir\Extension\Api\Event\Record\RecordSave;
use Contenir\Extension\Api\Event\Settings\SettingsPostSave;
use Contenir\Extension\Api\Event\Settings\SettingsPreSave;
use Contenir\Extension\Api\ExtensionInterface;
use Contenir\Extension\Api\Form\Field;
use Contenir\Extension\Api\Form\Form;
use Contenir\Extension\Api\Listener\Delivery;
use Contenir\Extension\Api\Listener\Scope;
use Contenir\Extension\Api\Operation\Operation;
use Contenir\Extension\Api\Registry;
use Contenir\Extension\Api\Settings\SettingsCategory;
use Contenir\Extension\Api\Settings\SettingsSection;
use Override;

/**
 * The add-on the conformance suite loads into a host: one small piece for
 * each behaviour the suite checks, each writing what it saw to the journal.
 *
 * @api
 */
final readonly class ProbeExtension implements ExtensionInterface
{
    public const string HANDLE = 'probe';

    public function __construct(
        private Journal $journal,
    ) {}

    #[Override]
    public function register(Registry $registry): void
    {
        $this->journal->registered();

        $registry->listen('change-value', RecordPreSave::class, ChangeValue::class);
        $registry->listen('stop', RecordPreSave::class, StopSave::class);
        $registry->listen('stop-settings', SettingsPreSave::class, StopSave::class);
        $registry->listen('reject-title', RecordPreSave::class, RejectTitle::class);
        $registry->listen('warn', RecordPreSave::class, WarnAndFlag::class);
        $registry->listen('record', RecordPreSave::class, RecordStage::class);
        $registry->listen('record', RecordSave::class, RecordStage::class);
        $registry->listen('record', RecordPostSave::class, RecordStage::class);
        $registry->listen('record-now', RecordPostSave::class, RecordStage::class, delivery: Delivery::Immediate);
        $registry->listen('record', RecordPreCreate::class, RecordStage::class);
        $registry->listen('record', RecordCreate::class, RecordStage::class);
        $registry->listen('record-now', RecordPostCreate::class, RecordStage::class, delivery: Delivery::Immediate);
        $registry->listen('record-now', SettingsPostSave::class, RecordStage::class, delivery: Delivery::Immediate);
        $registry->listen('email-only', RecordPreSave::class, RecordStage::class, Scope::fields('email', 'newsletter'));
        $registry->listen('fail', RecordPreSave::class, Fail::class);
        $registry->listen('fail', RecordSave::class, Fail::class);
        $registry->listen('fail', RecordCreate::class, Fail::class);
        $registry->listen('fail', RecordPostSave::class, Fail::class);
        $registry->listen('order-a', RecordPreSave::class, OrderA::class);
        $registry->listen('order-b', RecordPreSave::class, OrderB::class);
        $registry->listen('order-c', RecordPreSave::class, OrderC::class);
        $registry->listen('log', RecordPreSave::class, LogVisit::class);
        $registry->listen('name', RecordPreSave::class, NameAddOn::class);
        $registry->listen('forge', RecordPreSave::class, ForgeCoreEvent::class);
        $registry->listen('announce', RecordPreSave::class, Announce::class);
        $registry->listen('repeat', Announced::class, Repeat::class);

        $registry->settingsSection(new SettingsSection(
            name: self::HANDLE,
            title: 'Probe',
            category: SettingsCategory::System,
            form: new Form([new Field('note', 'Note', default: 'none yet'), new Field('colour', 'Colour')]),
            operations: [
                new Operation('note', 'Note', 'pencil', 'update', NoteOperation::class),
                new Operation('broken', 'Break', 'alert', 'update', BrokenOperation::class),
            ],
        ));

        $registry->provide(ProbeServiceInterface::class, ProbeServiceFactory::class);
        $registry->provide(BrokenServiceInterface::class, BrokenServiceFactory::class);
        $registry->provide(WrongServiceInterface::class, WrongServiceFactory::class);

        $registry->handler('status', StatusHandler::class);
        $registry->handler('export', ExportHandler::class);
        $registry->handler('remember', Remember::class);
        $registry->handler('broken', BrokenHandler::class);
        $registry->handler('records', RecordsHandler::class);
        $registry->handler('editor', EditorHandler::class);
        $registry->webhook('hook', Hook::class);
        $registry->webhook('file', FileHook::class);

        $registry->diagnostic('key', KeyCheck::class);
        $registry->diagnostic('cache', CacheCheck::class);
    }
}
