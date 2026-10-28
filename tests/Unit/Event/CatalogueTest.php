<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Event;

use Contenir\Extension\Api\Event\Catalogue;
use Contenir\Extension\Api\Event\CoreEventInterface;
use Contenir\Extension\Api\Event\Extension\ExtensionPostFail;
use Contenir\Extension\Api\Event\PostEventInterface;
use Contenir\Extension\Api\Event\PreEventInterface;
use Contenir\Extension\Api\Event\Record\RecordPostSave;
use Contenir\Extension\Api\Event\Record\RecordPreSave;
use Contenir\Extension\Api\Event\Record\RecordSave;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;
use function array_unique;
use function count;
use function is_a;
use function preg_match;

#[Group('unit')]
final class CatalogueTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function nameProvider(): array
    {
        return [
            'records'      => ['RecordPostSave'],
            'publishing'   => ['RecordPrePublish'],
            'settings'     => ['SettingsSave'],
            'assets'       => ['AssetPostUpload'],
            'permissions'  => ['PermissionsPreChange'],
            'sign-ins'     => ['UserPreSignIn'],
            'failed signs' => ['SignInPostFail'],
            'entries'      => ['EntryPostSubmit'],
            'statuses'     => ['EntryStatusChange'],
            'redaction'    => ['EntryPostRedact'],
            'cache'        => ['CachePostClear'],
            'migrations'   => ['MigrationsPostRun'],
            'failures'     => ['ExtensionPostFail'],
        ];
    }

    #[Test]
    #[DataProvider('nameProvider')]
    public function findsAnEventByItsShortName(string $name): void
    {
        $class = Catalogue::find($name);

        static::assertSame($name, null === $class ? null : Catalogue::name($class));
    }

    #[Test]
    public function findsNothingForAnUnknownName(): void
    {
        static::assertNull(Catalogue::find('RecordPostExplode'));
    }

    #[Test]
    public function holdsTheThreeStagesOfASave(): void
    {
        static::assertSame(
            [RecordPreSave::class, RecordSave::class, RecordPostSave::class],
            [Catalogue::find('RecordPreSave'), Catalogue::find('RecordSave'), Catalogue::find('RecordPostSave')],
        );
    }

    #[Test]
    public function listsOnlyCoreEventsWithUniqueNames(): void
    {
        $core = 0;
        foreach (Catalogue::EVENTS as $class) {
            $core += is_a($class, CoreEventInterface::class, allow_string: true) ? 1 : 0;
        }

        $names = array_map(Catalogue::name(...), Catalogue::EVENTS);

        static::assertSame([55, 55, 55], [count(Catalogue::EVENTS), $core, count(array_unique($names))]);
    }

    #[Test]
    public function namesAnEventByItsClass(): void
    {
        static::assertSame(
            ['RecordPreSave', 'ExtensionPostFail', 'Unqualified'],
            [
                Catalogue::name(RecordPreSave::class),
                Catalogue::name(ExtensionPostFail::class),
                Catalogue::name('Unqualified'),
            ],
        );
    }

    #[Test]
    public function stagesEveryEventByItsName(): void
    {
        $misnamed = [];
        foreach (Catalogue::EVENTS as $class) {
            $name = Catalogue::name($class);
            $pre  = 1 === preg_match('/^[A-Z][a-z]+Pre[A-Z]/', $name);
            $post = 1 === preg_match('/^[A-Z][a-z]+(Post|InPost)[A-Z]/', $name);
            if (
                is_a($class, PreEventInterface::class, allow_string: true) !== $pre
                || is_a($class, PostEventInterface::class, allow_string: true) !== $post
            ) {
                $misnamed[] = $name;
            }
        }

        static::assertSame([], $misnamed);
    }
}
