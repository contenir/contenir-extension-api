<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit;

use Contenir\Extension\Api\Diagnostic\DiagnosticDeclaration;
use Contenir\Extension\Api\Event\Cache\CachePostClear;
use Contenir\Extension\Api\Event\Record\RecordPostDelete;
use Contenir\Extension\Api\Event\Record\RecordPostSave;
use Contenir\Extension\Api\Event\Record\RecordPreSave;
use Contenir\Extension\Api\Event\Record\RecordSave;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Field\FieldTypeDeclaration;
use Contenir\Extension\Api\Form\Form;
use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Handler\HandlerDeclaration;
use Contenir\Extension\Api\Listener\Delivery;
use Contenir\Extension\Api\Listener\ListenerDeclaration;
use Contenir\Extension\Api\Listener\Scope;
use Contenir\Extension\Api\Migration\MigrationSet;
use Contenir\Extension\Api\Migration\Target;
use Contenir\Extension\Api\Module\Component;
use Contenir\Extension\Api\Module\Module;
use Contenir\Extension\Api\Panel\PanelDeclaration;
use Contenir\Extension\Api\Registry;
use Contenir\Extension\Api\Schedule\Frequency;
use Contenir\Extension\Api\Schedule\ScheduleDeclaration;
use Contenir\Extension\Api\Script\ScriptDeclaration;
use Contenir\Extension\Api\Settings\SettingsCategory;
use Contenir\Extension\Api\Settings\SettingsSection;
use Contenir\Extension\Api\Tests\TestAsset\Extension\ApiKeyCheck;
use Contenir\Extension\Api\Tests\TestAsset\Extension\ConnectHandler;
use Contenir\Extension\Api\Tests\TestAsset\Extension\Event\SubscriberPostSync;
use Contenir\Extension\Api\Tests\TestAsset\Extension\Event\SubscriberSync;
use Contenir\Extension\Api\Tests\TestAsset\Extension\ListFieldType;
use Contenir\Extension\Api\Tests\TestAsset\Extension\ResyncTask;
use Contenir\Extension\Api\Tests\TestAsset\Extension\ShadeFieldType;
use Contenir\Extension\Api\Tests\TestAsset\Extension\SignedWebhook;
use Contenir\Extension\Api\Tests\TestAsset\Extension\SignupScripts;
use Contenir\Extension\Api\Tests\TestAsset\Extension\SubscriberPanel;
use Contenir\Extension\Api\Tests\TestAsset\Extension\SyncSubscriber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_keys;

#[Group('unit')]
final class RegistryTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function foreignSectionNameProvider(): array
    {
        return [
            'another name'                => ['cache'],
            'the handle without a dash'   => ['newsletters'],
            'the handle not at the start' => ['old-newsletter'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function sectionNameProvider(): array
    {
        return [
            'the handle'            => ['newsletter'],
            'the handle and a dash' => ['newsletter-lists'],
        ];
    }

    #[Test]
    public function acceptsEveryChangeOnAnEventWithoutValues(): void
    {
        $registry = new Registry('newsletter');
        $registry->listen('warm', CachePostClear::class, SyncSubscriber::class, Scope::all());

        static::assertCount(1, $registry->registration()->listeners);
    }

    #[Test]
    public function declaresAModuleWithItsPieces(): void
    {
        $registry = new Registry('formbuilder');
        $registry->module('/addon/admin.xml');
        $registry->assets('/addon/public');
        $registry->component('[data-form-editor]', 'js/form-editor.js');
        $registry->component('[data-form-preview]', 'js/form-preview.js');
        $registry->templates('/addon/templates');

        static::assertEquals(
            new Module(
                '/addon/admin.xml',
                '/addon/public',
                [
                    new Component('[data-form-editor]', 'js/form-editor.js'),
                    new Component('[data-form-preview]', 'js/form-preview.js'),
                ],
                '/addon/templates',
            ),
            $registry->registration()->module,
        );
    }

    #[Test]
    public function declaresAnImportableFieldTypeAsAnyOther(): void
    {
        $registry = new Registry('newsletter');
        $registry->fieldType('shade', ShadeFieldType::class);

        static::assertEquals(
            ['newsletter:shade' => new FieldTypeDeclaration('newsletter:shade', 'shade', ShadeFieldType::class)],
            $registry->registration()->fieldTypes,
        );
    }

    #[Test]
    public function declaresAPostListenerToRunInTheBackground(): void
    {
        $registry = new Registry('newsletter');
        $registry->listen(
            'sync-subscriber',
            RecordPostSave::class,
            SyncSubscriber::class,
            Scope::fields('email', 'name'),
        );

        static::assertEquals(
            [ListenerDeclaration::of(
                'sync-subscriber',
                RecordPostSave::class,
                SyncSubscriber::class,
                Scope::fields('email', 'name'),
                Delivery::Background,
            )],
            $registry->registration()->listeners,
        );
    }

    #[Test]
    public function declaresDependencies(): void
    {
        $registry = new Registry('events');
        $registry->dependsOn('commerce');
        $registry->dependsOn('formbuilder');

        static::assertEquals(
            ['commerce' => new Handle('commerce'), 'formbuilder' => new Handle('formbuilder')],
            $registry->registration()->dependencies,
        );
    }

    #[Test]
    public function declaresDiagnostics(): void
    {
        $registry = new Registry('newsletter');
        $registry->diagnostic('api-key', ApiKeyCheck::class);

        static::assertEquals(
            ['api-key' => new DiagnosticDeclaration('api-key', ApiKeyCheck::class)],
            $registry->registration()->diagnostics,
        );
    }

    #[Test]
    public function declaresFieldTypes(): void
    {
        $registry = new Registry('newsletter');
        $registry->fieldType('mailing-list', ListFieldType::class);

        static::assertEquals(
            ['newsletter:mailing-list' => new FieldTypeDeclaration(
                'newsletter:mailing-list',
                'mailing-list',
                ListFieldType::class,
            )],
            $registry->registration()->fieldTypes,
        );
    }

    #[Test]
    public function declaresIcons(): void
    {
        $registry = new Registry('newsletter');
        $registry->icons('/addon/icons');

        static::assertSame('/addon/icons', $registry->registration()->icons);
    }

    #[Test]
    public function declaresMigrationsOnTheCmsDatabase(): void
    {
        $registry = new Registry('newsletter');
        $registry->migrations('/addon/site', 'SiteMigrations');
        $registry->migrations('/addon/cms', 'CmsMigrations', Target::Cms);

        static::assertEquals(
            [
                new MigrationSet('/addon/site', 'SiteMigrations', Target::Site),
                new MigrationSet('/addon/cms', 'CmsMigrations', Target::Cms),
            ],
            $registry->registration()->migrations,
        );
    }

    #[Test]
    public function declaresMigrationsOnTheSiteDatabaseByDefault(): void
    {
        $registry = new Registry('newsletter');
        $registry->migrations('/addon/migrations', 'NewsletterMigrations');

        static::assertEquals(
            [new MigrationSet('/addon/migrations', 'NewsletterMigrations', Target::Site)],
            $registry->registration()->migrations,
        );
    }

    #[Test]
    public function declaresNoRecordPanelsUntilAsked(): void
    {
        static::assertSame([], (new Registry('newsletter'))->registration()->panels);
    }

    #[Test]
    public function declaresRecordPanels(): void
    {
        $registry = new Registry('newsletter');
        $registry->panel('subscriber', SubscriberPanel::class);

        static::assertEquals(
            ['subscriber' => new PanelDeclaration('subscriber', SubscriberPanel::class)],
            $registry->registration()->panels,
        );
    }

    #[Test]
    public function declaresSchedules(): void
    {
        $registry  = new Registry('newsletter');
        $frequency = Frequency::everyMinutes(60);
        $registry->schedule('resync', ResyncTask::class, $frequency);

        static::assertEquals(
            ['resync' => new ScheduleDeclaration('resync', ResyncTask::class, $frequency)],
            $registry->registration()->schedules,
        );
    }

    #[Test]
    public function declaresScripts(): void
    {
        $registry = new Registry('newsletter');
        $registry->script('signup', SignupScripts::class);

        static::assertEquals(
            ['signup' => new ScriptDeclaration('signup', SignupScripts::class)],
            $registry->registration()->scripts,
        );
    }

    #[Test]
    #[DataProvider('sectionNameProvider')]
    public function declaresSettingsSectionsUnderItsHandle(string $name): void
    {
        $registry = new Registry('newsletter');
        $section  = $this->section($name);
        $registry->settingsSection($section);

        static::assertSame([$name => $section], $registry->registration()->settingsSections);
    }

    #[Test]
    public function handsBackItsHandle(): void
    {
        $registry = new Registry('newsletter');

        static::assertSame($registry->handle(), $registry->registration()->handle);
    }

    #[Test]
    public function keepsPreListenersOutOfTheBackground(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Listener "check-meta" cannot run in the background: only Post listeners can');

        (new Registry('seo'))->listen(
            'check-meta',
            RecordPreSave::class,
            SyncSubscriber::class,
            delivery: Delivery::Background,
        );
    }

    #[Test]
    public function keepsSettingsSectionsInOrder(): void
    {
        $registry = new Registry('newsletter');
        $registry->settingsSection($this->section('newsletter'));
        $registry->settingsSection($this->section('newsletter-lists'));

        static::assertSame(['newsletter', 'newsletter-lists'], array_keys($registry->registration()->settingsSections));
    }

    #[Test]
    public function keepsTemplatesForModules(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Only a module may ship templates: declare module() first');

        (new Registry('newsletter'))->templates('/addon/templates');
    }

    #[Test]
    public function letsAnyAddOnShipAssetsAndComponents(): void
    {
        $registry = new Registry('newsletter');
        $registry->assets('/addon/public');
        $registry->component('[data-list-picker]', 'js/list-picker.js');

        static::assertEquals(
            new Module(null, '/addon/public', [new Component('[data-list-picker]', 'js/list-picker.js')]),
            $registry->registration()->module,
        );
    }

    #[Test]
    public function letsAPostListenerAskToRunImmediately(): void
    {
        $registry = new Registry('newsletter');
        $registry->listen(
            'sync-subscriber',
            RecordPostSave::class,
            SyncSubscriber::class,
            delivery: Delivery::Immediate,
        );

        static::assertSame(Delivery::Immediate, $registry->registration()->listeners[0]->delivery ?? null);
    }

    #[Test]
    public function letsAWebhookShareAHandlersName(): void
    {
        $registry = new Registry('newsletter');
        $registry->handler('connect', ConnectHandler::class);
        $registry->webhook('connect', SignedWebhook::class);

        static::assertSame(['connect'], array_keys($registry->registration()->webhooks));
    }

    #[Test]
    public function letsOneNameListenToSeveralEvents(): void
    {
        $registry = new Registry('newsletter');
        $registry->listen('sync-subscriber', RecordPostSave::class, SyncSubscriber::class);
        $registry->listen('sync-subscriber', RecordPostDelete::class, SyncSubscriber::class);

        static::assertCount(2, $registry->registration()->listeners);
    }

    #[Test]
    public function letsPostListenersAskForTheBackground(): void
    {
        $registry = new Registry('newsletter');
        $registry->listen('sync', RecordPostSave::class, SyncSubscriber::class, delivery: Delivery::Background);

        static::assertSame(Delivery::Background, $registry->registration()->listeners[0]->delivery ?? null);
    }

    #[Test]
    public function listensToAnotherAddOnsEvents(): void
    {
        $registry = new Registry('crm');
        $registry->listen('note-sync', SubscriberPostSync::class, SyncSubscriber::class);
        $registry->listen('note-sync-now', SubscriberSync::class, SyncSubscriber::class);

        static::assertSame(
            [Delivery::Background, Delivery::Immediate],
            [
                $registry->registration()->listeners[0]->delivery ?? null,
                $registry->registration()->listeners[1]->delivery ?? null,
            ],
        );
    }

    #[Test]
    public function listensToEveryChangeByDefault(): void
    {
        $registry = new Registry('newsletter');
        $registry->listen('sync-subscriber', RecordPostSave::class, SyncSubscriber::class);

        static::assertEquals(Scope::all(), $registry->registration()->listeners[0]->scope ?? null);
    }

    #[Test]
    public function mountsHandlersUnderTheAdminPath(): void
    {
        $registry = new Registry('newsletter');
        $registry->handler('connect', ConnectHandler::class);

        static::assertEquals(
            ['connect' => new HandlerDeclaration(
                'connect',
                ConnectHandler::class,
                '/admin/extension/newsletter/connect',
            )],
            $registry->registration()->handlers,
        );
    }

    #[Test]
    public function mountsWebhooksUnderThePublicPath(): void
    {
        $registry = new Registry('newsletter');
        $registry->webhook('unsubscribe', SignedWebhook::class);

        static::assertEquals(
            ['unsubscribe' => new HandlerDeclaration(
                'unsubscribe',
                SignedWebhook::class,
                '/api/extension/newsletter/unsubscribe',
            )],
            $registry->registration()->webhooks,
        );
    }

    #[Test]
    public function prefixesABuiltInNameSoItCannotClash(): void
    {
        $registry = new Registry('newsletter');
        $registry->fieldType('select', ListFieldType::class);

        static::assertSame(['newsletter:select'], array_keys($registry->registration()->fieldTypes));
    }

    #[Test]
    public function rejectsAClassThatIsNotAnEvent(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Event class "' . SyncSubscriber::class . '"');

        (new Registry('newsletter'))->listen('sync', SyncSubscriber::class, SyncSubscriber::class);
    }

    #[Test]
    public function rejectsAComponentWithoutAScript(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('A component script must not be empty');

        (new Registry('newsletter'))->component('[data-a]', '');
    }

    #[Test]
    public function rejectsAComponentWithoutASelector(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('A component selector must not be empty');

        (new Registry('newsletter'))->component('', 'js/a.js');
    }

    #[Test]
    public function rejectsADependencyDeclaredTwice(): void
    {
        $registry = new Registry('events');
        $registry->dependsOn('commerce');

        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Dependency "commerce" is already declared');

        $registry->dependsOn('commerce');
    }

    #[Test]
    public function rejectsADiagnosticClassOfTheWrongKind(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        (new Registry('newsletter'))->diagnostic('api-key', ResyncTask::class);
    }

    #[Test]
    public function rejectsAFieldScopeOnAnEventWithoutValues(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage(
            'Listener "warm" has a field scope, but ' . CachePostClear::class . ' is not about changing values',
        );

        (new Registry('newsletter'))->listen(
            'warm',
            CachePostClear::class,
            SyncSubscriber::class,
            Scope::fields('title'),
        );
    }

    #[Test]
    public function rejectsAFieldTypeClassOfTheWrongKind(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        (new Registry('newsletter'))->fieldType('mailing-list', ResyncTask::class);
    }

    #[Test]
    public function rejectsAHandlerClassOfTheWrongKind(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        (new Registry('newsletter'))->handler('connect', SignedWebhook::class);
    }

    #[Test]
    public function rejectsAListenerOfTheWrongKind(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Listener class "' . ResyncTask::class . '"');

        (new Registry('newsletter'))->listen('sync', RecordPostSave::class, ResyncTask::class);
    }

    #[Test]
    public function rejectsAMalformedDependency(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        (new Registry('events'))->dependsOn('Commerce');
    }

    #[Test]
    public function rejectsAMalformedDiagnosticName(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Diagnostic name "Key"');

        (new Registry('newsletter'))->diagnostic('Key', ApiKeyCheck::class);
    }

    #[Test]
    public function rejectsAMalformedFieldTypeName(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        (new Registry('newsletter'))->fieldType('MailingList', ListFieldType::class);
    }

    #[Test]
    public function rejectsAMalformedHandle(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        new Registry('News');
    }

    #[Test]
    public function rejectsAMalformedHandlerName(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Handler name "Connect"');

        (new Registry('newsletter'))->handler('Connect', ConnectHandler::class);
    }

    #[Test]
    public function rejectsAMalformedListenerName(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Listener name "Sync"');

        (new Registry('newsletter'))->listen('Sync', RecordPostSave::class, SyncSubscriber::class);
    }

    #[Test]
    public function rejectsAMalformedMigrationsNamespace(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        (new Registry('newsletter'))->migrations('/addon/migrations', 'Migrations\\');
    }

    #[Test]
    public function rejectsAMalformedPanelName(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Panel name "Subscriber"');

        (new Registry('newsletter'))->panel('Subscriber', SubscriberPanel::class);
    }

    #[Test]
    public function rejectsAMalformedScheduleName(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Schedule name "Resync"');

        (new Registry('newsletter'))->schedule('Resync', ResyncTask::class, Frequency::everyMinutes(5));
    }

    #[Test]
    public function rejectsAMalformedScriptName(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Script name "Signup"');

        (new Registry('newsletter'))->script('Signup', SignupScripts::class);
    }

    #[Test]
    public function rejectsAMalformedWebhookName(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Webhook name "Hook"');

        (new Registry('newsletter'))->webhook('Hook', SignedWebhook::class);
    }

    #[Test]
    public function rejectsAMigrationsNamespaceDeclaredTwice(): void
    {
        $registry = new Registry('newsletter');
        $registry->migrations('/addon/migrations', 'Migrations');

        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Migration namespace "Migrations" is already declared');

        $registry->migrations('/addon/other', 'Migrations', Target::Cms);
    }

    #[Test]
    public function rejectsAModuleDeclaredTwice(): void
    {
        $registry = new Registry('formbuilder');
        $registry->module('/addon/admin.xml');

        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('The add-on has already declared its module');

        $registry->module('/addon/other.xml');
    }

    #[Test]
    public function rejectsANameListeningToTheSameEventTwice(): void
    {
        $registry = new Registry('newsletter');
        $registry->listen('sync', RecordPostSave::class, SyncSubscriber::class);

        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Listener "sync" already listens to ' . RecordPostSave::class);

        $registry->listen('sync', RecordPostSave::class, SyncSubscriber::class);
    }

    #[Test]
    public function rejectsAnEmptyIconsPath(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('The icons path must not be empty');

        (new Registry('newsletter'))->icons('');
    }

    #[Test]
    public function rejectsAnEmptyMigrationsPath(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('A migrations path must not be empty');

        (new Registry('newsletter'))->migrations('', 'Migrations');
    }

    #[Test]
    public function rejectsAnEmptyModulePath(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('The module path must not be empty');

        (new Registry('formbuilder'))->module(' ');
    }

    #[Test]
    public function rejectsAPanelNameTakenTwice(): void
    {
        $registry = new Registry('newsletter');
        $registry->panel('subscriber', SubscriberPanel::class);

        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('subscriber');

        $registry->panel('subscriber', SubscriberPanel::class);
    }

    #[Test]
    public function rejectsAPanelThatIsNotARecordPanel(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Record panel');

        (new Registry('newsletter'))->panel('subscriber', SignupScripts::class);
    }

    #[Test]
    public function rejectsAScheduledClassOfTheWrongKind(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        (new Registry('newsletter'))->schedule('resync', ApiKeyCheck::class, Frequency::everyMinutes(5));
    }

    #[Test]
    public function rejectsAScriptProviderOfTheWrongKind(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        (new Registry('newsletter'))->script('signup', ResyncTask::class);
    }

    #[Test]
    public function rejectsASecondComponentForTheSameSelector(): void
    {
        $registry = new Registry('newsletter');
        $registry->component('[data-a]', 'js/a.js');

        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Component "[data-a]" is already declared');

        $registry->component('[data-a]', 'js/b.js');
    }

    #[Test]
    public function rejectsASecondDiagnosticWithTheSameName(): void
    {
        $registry = new Registry('newsletter');
        $registry->diagnostic('api-key', ApiKeyCheck::class);

        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Diagnostic "api-key" is already declared');

        $registry->diagnostic('api-key', ApiKeyCheck::class);
    }

    #[Test]
    public function rejectsASecondFieldTypeWithTheSameName(): void
    {
        $registry = new Registry('newsletter');
        $registry->fieldType('mailing-list', ListFieldType::class);

        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Field type "newsletter:mailing-list" is already declared');

        $registry->fieldType('mailing-list', ListFieldType::class);
    }

    #[Test]
    public function rejectsASecondHandlerWithTheSameName(): void
    {
        $registry = new Registry('newsletter');
        $registry->handler('connect', ConnectHandler::class);

        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Handler "connect" is already declared');

        $registry->handler('connect', ConnectHandler::class);
    }

    #[Test]
    public function rejectsASecondScheduleWithTheSameName(): void
    {
        $registry = new Registry('newsletter');
        $registry->schedule('resync', ResyncTask::class, Frequency::everyMinutes(5));

        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Schedule "resync" is already declared');

        $registry->schedule('resync', ResyncTask::class, Frequency::cron('0 * * * *'));
    }

    #[Test]
    public function rejectsASecondScriptWithTheSameName(): void
    {
        $registry = new Registry('newsletter');
        $registry->script('signup', SignupScripts::class);

        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Script "signup" is already declared');

        $registry->script('signup', SignupScripts::class);
    }

    #[Test]
    public function rejectsASecondSettingsSectionWithTheSameName(): void
    {
        $registry = new Registry('newsletter');
        $registry->settingsSection($this->section('newsletter'));

        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Settings section "newsletter" is already declared');

        $registry->settingsSection($this->section('newsletter'));
    }

    #[Test]
    public function rejectsASecondWebhookWithTheSameName(): void
    {
        $registry = new Registry('newsletter');
        $registry->webhook('unsubscribe', SignedWebhook::class);

        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Webhook "unsubscribe" is already declared');

        $registry->webhook('unsubscribe', SignedWebhook::class);
    }

    #[Test]
    public function rejectsAssetsDeclaredTwice(): void
    {
        $registry = new Registry('newsletter');
        $registry->assets('/addon/public');

        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('The add-on has already declared its assets');

        $registry->assets('/addon/other');
    }

    #[Test]
    public function rejectsAWebhookClassOfTheWrongKind(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        (new Registry('newsletter'))->webhook('unsubscribe', ConnectHandler::class);
    }

    #[Test]
    public function rejectsDependingOnItself(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Add-on "events" cannot depend on itself');

        (new Registry('events'))->dependsOn('events');
    }

    #[Test]
    public function rejectsIconsDeclaredTwice(): void
    {
        $registry = new Registry('newsletter');
        $registry->icons('/addon/icons');

        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('The add-on has already declared its icons');

        $registry->icons('/addon/more-icons');
    }

    #[Test]
    #[DataProvider('foreignSectionNameProvider')]
    public function rejectsSettingsSectionsOutsideItsHandle(string $name): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage(
            "Settings section \"{$name}\" must be named \"newsletter\" or start with \"newsletter-\"",
        );

        (new Registry('newsletter'))->settingsSection($this->section($name));
    }

    #[Test]
    public function rejectsTemplatesDeclaredTwice(): void
    {
        $registry = new Registry('formbuilder');
        $registry->module('/addon/admin.xml');
        $registry->templates('/addon/templates');

        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('The add-on has already declared its templates');

        $registry->templates('/addon/other');
    }

    #[Test]
    public function reservesTheFixHandlerName(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Handler name "fix" is reserved');

        (new Registry('newsletter'))->handler('fix', ConnectHandler::class);
    }

    #[Test]
    public function runsPreAndMainStageListenersImmediately(): void
    {
        $registry = new Registry('slug-tools');
        $registry->listen('normalise-slug', RecordPreSave::class, SyncSubscriber::class);
        $registry->listen('write-index', RecordSave::class, SyncSubscriber::class, delivery: Delivery::Immediate);

        $listeners = $registry->registration()->listeners;

        static::assertSame(
            [Delivery::Immediate, Delivery::Immediate],
            [$listeners[0]->delivery ?? null, $listeners[1]->delivery ?? null],
        );
    }

    #[Test]
    public function startsEmpty(): void
    {
        $registration = (new Registry('newsletter'))->registration();

        static::assertEquals(
            [[], [], null, [], [], [], [], [], [], [], new Module(), []],
            [
                $registration->settingsSections,
                $registration->fieldTypes,
                $registration->icons,
                $registration->diagnostics,
                $registration->listeners,
                $registration->handlers,
                $registration->webhooks,
                $registration->migrations,
                $registration->schedules,
                $registration->scripts,
                $registration->module,
                $registration->dependencies,
            ],
        );
    }

    #[Test]
    public function takesAHandle(): void
    {
        $handle = new Handle('newsletter');

        static::assertSame($handle, (new Registry($handle))->handle());
    }

    #[Test]
    public function takesAHandleAsAString(): void
    {
        static::assertSame('newsletter', (new Registry('newsletter'))->handle()->value);
    }

    private function section(string $name): SettingsSection
    {
        return new SettingsSection($name, 'Newsletter', SettingsCategory::Integration, new Form());
    }
}
