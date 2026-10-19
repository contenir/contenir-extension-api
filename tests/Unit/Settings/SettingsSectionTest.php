<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Settings;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Form\Form;
use Contenir\Extension\Api\Operation\Operation;
use Contenir\Extension\Api\Settings\SettingsCategory;
use Contenir\Extension\Api\Settings\SettingsSection;
use Contenir\Extension\Api\Tests\TestAsset\Extension\PurgeHandler;
use Contenir\Extension\Api\Tests\TestAsset\Extension\ReadConfig;
use Contenir\Extension\Api\Tests\TestAsset\Extension\ResyncTask;
use Contenir\Extension\Api\Tests\TestAsset\Extension\WriteConfig;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SettingsSectionTest extends TestCase
{
    #[Test]
    public function appendsWithNothingElseByDefault(): void
    {
        $section = new SettingsSection('newsletter', 'Newsletter', SettingsCategory::Integration, new Form());

        static::assertSame(
            [[], [], [], [], null],
            [
                $section->operations,
                $section->contributions,
                $section->saveListeners,
                $section->rowHydrators,
                $section->order,
            ],
        );
    }

    #[Test]
    public function declaresASectionWithItsParts(): void
    {
        $purge   = new Operation('purge', 'Purge', 'trash', 'purge', PurgeHandler::class);
        $section = new SettingsSection(
            name: 'newsletter',
            title: 'Newsletter',
            category: SettingsCategory::Integration,
            form: new Form(),
            operations: [$purge],
            saveListeners: [WriteConfig::class],
            rowHydrators: [ReadConfig::class],
            order: 20,
        );

        static::assertSame(
            [[$purge], [WriteConfig::class], [ReadConfig::class], 20],
            [$section->operations, $section->saveListeners, $section->rowHydrators, $section->order],
        );
    }

    #[Test]
    public function rejectsAMalformedName(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        new SettingsSection('Newsletter', 'Newsletter', SettingsCategory::Integration, new Form());
    }

    #[Test]
    public function rejectsAnEmptyTitle(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('A settings section title must not be empty');

        new SettingsSection('newsletter', '', SettingsCategory::Integration, new Form());
    }

    #[Test]
    public function rejectsAnOperationWhoseHandlerIsNotAnOperationHandler(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        new SettingsSection('newsletter', 'Newsletter', SettingsCategory::Integration, new Form(), [
            new Operation('purge', 'Purge', 'trash', 'purge', ResyncTask::class),
        ]);
    }

    #[Test]
    public function rejectsARowHydratorOfTheWrongKind(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Row hydrator class "' . WriteConfig::class . '"');

        new SettingsSection(
            'newsletter',
            'Newsletter',
            SettingsCategory::Integration,
            new Form(),
            rowHydrators: [
                WriteConfig::class,
            ],
        );
    }

    #[Test]
    public function rejectsASaveListenerOfTheWrongKind(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Save listener class "' . ReadConfig::class . '"');

        new SettingsSection(
            'newsletter',
            'Newsletter',
            SettingsCategory::Integration,
            new Form(),
            saveListeners: [
                ReadConfig::class,
            ],
        );
    }

    #[Test]
    public function rejectsOperationsSharingAName(): void
    {
        $purge = new Operation('purge', 'Purge', 'trash', 'purge', PurgeHandler::class);

        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Operation "purge" is already declared');

        new SettingsSection('newsletter', 'Newsletter', SettingsCategory::Integration, new Form(), [$purge, $purge]);
    }
}
