<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Event;

use Closure;
use Contenir\Extension\Api\Event\Asset\AssetDelete;
use Contenir\Extension\Api\Event\Asset\AssetPostDelete;
use Contenir\Extension\Api\Event\Asset\AssetPostUpload;
use Contenir\Extension\Api\Event\Asset\AssetPreDelete;
use Contenir\Extension\Api\Event\Asset\AssetPreUpload;
use Contenir\Extension\Api\Event\Asset\AssetUpload;
use Contenir\Extension\Api\Event\Cache\CachePostClear;
use Contenir\Extension\Api\Event\Cache\CachePreClear;
use Contenir\Extension\Api\Event\Change;
use Contenir\Extension\Api\Event\ChangeEventInterface;
use Contenir\Extension\Api\Event\Entry\EntryPostRedact;
use Contenir\Extension\Api\Event\Entry\EntryPostStatusChange;
use Contenir\Extension\Api\Event\Entry\EntryPostSubmit;
use Contenir\Extension\Api\Event\Entry\EntryPreStatusChange;
use Contenir\Extension\Api\Event\Entry\EntryPreSubmit;
use Contenir\Extension\Api\Event\Entry\EntryStatusChange;
use Contenir\Extension\Api\Event\Extension\ExtensionPostFail;
use Contenir\Extension\Api\Event\Extension\FailurePoint;
use Contenir\Extension\Api\Event\Migration\MigrationsPostRun;
use Contenir\Extension\Api\Event\Outcome;
use Contenir\Extension\Api\Event\Permissions\PermissionsChange;
use Contenir\Extension\Api\Event\Permissions\PermissionsPostChange;
use Contenir\Extension\Api\Event\Permissions\PermissionsPreChange;
use Contenir\Extension\Api\Event\PostEventInterface;
use Contenir\Extension\Api\Event\Record\RecordPostSave;
use Contenir\Extension\Api\Event\Record\RecordPreCreate;
use Contenir\Extension\Api\Event\Record\RecordPreSave;
use Contenir\Extension\Api\Event\Record\RecordSave;
use Contenir\Extension\Api\Event\Settings\SettingsPostSave;
use Contenir\Extension\Api\Event\Settings\SettingsPreSave;
use Contenir\Extension\Api\Event\Settings\SettingsSave;
use Contenir\Extension\Api\Event\User\SignInPostFail;
use Contenir\Extension\Api\Event\User\UserPostSignIn;
use Contenir\Extension\Api\Event\User\UserPreSignIn;
use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Migration\Target;
use Contenir\Extension\Api\Url\RecordReference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function get_object_vars;

#[Group('unit')]
final class EventsTest extends TestCase
{
    /**
     * @return array<string, array{Closure(): object}>
     */
    public static function changeEventProvider(): array
    {
        $change = self::change();

        return [
            'RecordPreSave'         => [static fn(): object => new RecordPreSave('article', 5, $change)],
            'RecordSave'            => [static fn(): object => new RecordSave('article', 5, $change)],
            'RecordPostSave'        => [static fn(): object => new RecordPostSave('article', 5, $change)],
            'SettingsPreSave'       => [static fn(): object => new SettingsPreSave('newsletter', $change)],
            'SettingsSave'          => [static fn(): object => new SettingsSave('newsletter', $change)],
            'SettingsPostSave'      => [static fn(): object => new SettingsPostSave('newsletter', $change)],
            'PermissionsPreChange'  => [static fn(): object => new PermissionsPreChange('editor', $change)],
            'PermissionsChange'     => [static fn(): object => new PermissionsChange('editor', $change)],
            'PermissionsPostChange' => [static fn(): object => new PermissionsPostChange('editor', $change)],
            'EntryPreSubmit'        => [static fn(): object => new EntryPreSubmit('contact', $change)],
            'EntryPostSubmit'       => [static fn(): object => new EntryPostSubmit('contact', 9, $change)],
            'EntryPreStatusChange'  => [static fn(): object => new EntryPreStatusChange('contact', 9, $change)],
            'EntryStatusChange'     => [static fn(): object => new EntryStatusChange('contact', 9, $change)],
            'EntryPostStatusChange' => [static fn(): object => new EntryPostStatusChange('contact', 9, $change)],
        ];
    }

    /**
     * @return array<string, array{Closure(): object}>
     */
    public static function donePostEventProvider(): array
    {
        $change = self::change();

        return [
            'RecordPostSave'        => [static fn(): object => new RecordPostSave('article', 5, $change)],
            'SettingsPostSave'      => [static fn(): object => new SettingsPostSave('newsletter', $change)],
            'PermissionsPostChange' => [static fn(): object => new PermissionsPostChange('editor', $change)],
            'AssetPostUpload'       => [static fn(): object => new AssetPostUpload('a/b.jpg')],
            'AssetPostDelete'       => [static fn(): object => new AssetPostDelete('a/b.jpg')],
            'UserPostSignIn'        => [static fn(): object => new UserPostSignIn('ann', '10.0.0.1')],
            'EntryPostSubmit'       => [static fn(): object => new EntryPostSubmit('contact', 9, $change)],
            'EntryPostStatusChange' => [static fn(): object => new EntryPostStatusChange('contact', 9, $change)],
            'EntryPostRedact'       => [static fn(): object => new EntryPostRedact('contact', [])],
            'CachePostClear'        => [static fn(): object => new CachePostClear('all')],
            'MigrationsPostRun'     => [static fn(): object => new MigrationsPostRun(Target::Cms, [])],
        ];
    }

    /**
     * @return array<string, array{Closure(): object, array<string, mixed>}>
     */
    public static function payloadProvider(): array
    {
        $change = self::change();

        return [
            'RecordPreSave'         => [
                static fn(): object => new RecordPreSave('article', 5, $change),
                ['section' => 'article', 'id' => 5],
            ],
            'RecordSave'            => [
                static fn(): object => new RecordSave('article', '5', $change),
                ['section' => 'article', 'id' => '5'],
            ],
            'RecordPostSave'        => [
                static fn(): object => new RecordPostSave('article', null, $change),
                ['section' => 'article', 'id' => null],
            ],
            'SettingsPreSave'       => [
                static fn(): object => new SettingsPreSave('newsletter', $change),
                ['section' => 'newsletter'],
            ],
            'SettingsSave'          => [
                static fn(): object => new SettingsSave('newsletter', $change),
                ['section' => 'newsletter'],
            ],
            'SettingsPostSave'      => [
                static fn(): object => new SettingsPostSave('newsletter', $change),
                ['section' => 'newsletter'],
            ],
            'PermissionsPreChange'  => [
                static fn(): object => new PermissionsPreChange('editor', $change),
                ['role' => 'editor'],
            ],
            'PermissionsChange'     => [
                static fn(): object => new PermissionsChange('editor', $change),
                ['role' => 'editor'],
            ],
            'PermissionsPostChange' => [
                static fn(): object => new PermissionsPostChange('editor', $change),
                ['role' => 'editor'],
            ],
            'AssetPreUpload'        => [static fn(): object => new AssetPreUpload('a/b.jpg'), ['path' => 'a/b.jpg']],
            'AssetUpload'           => [static fn(): object => new AssetUpload('a/b.jpg'), ['path' => 'a/b.jpg']],
            'AssetPostUpload'       => [static fn(): object => new AssetPostUpload('a/b.jpg'), ['path' => 'a/b.jpg']],
            'AssetPreDelete'        => [static fn(): object => new AssetPreDelete('a/b.jpg'), ['path' => 'a/b.jpg']],
            'AssetDelete'           => [static fn(): object => new AssetDelete('a/b.jpg'), ['path' => 'a/b.jpg']],
            'AssetPostDelete'       => [static fn(): object => new AssetPostDelete('a/b.jpg'), ['path' => 'a/b.jpg']],
            'UserPreSignIn'         => [
                static fn(): object => new UserPreSignIn('ann', '10.0.0.1'),
                ['username' => 'ann', 'ip' => '10.0.0.1'],
            ],
            'UserPostSignIn'        => [
                static fn(): object => new UserPostSignIn('ann', '10.0.0.1'),
                ['username' => 'ann', 'ip' => '10.0.0.1'],
            ],
            'SignInPostFail'        => [
                static fn(): object => new SignInPostFail('ann', '10.0.0.1', 'Wrong password'),
                [
                    'username' => 'ann',
                    'ip'       => '10.0.0.1',
                ],
            ],
            'EntryPreSubmit'        => [
                static fn(): object => new EntryPreSubmit('contact', $change),
                ['form' => 'contact'],
            ],
            'EntryPostSubmit'       => [
                static fn(): object => new EntryPostSubmit('contact', 9, $change),
                ['form' => 'contact', 'id' => 9],
            ],
            'EntryPreStatusChange'  => [
                static fn(): object => new EntryPreStatusChange('contact', 9, $change),
                ['form' => 'contact', 'id' => 9],
            ],
            'EntryStatusChange'     => [
                static fn(): object => new EntryStatusChange('contact', 9, $change),
                ['form' => 'contact', 'id' => 9],
            ],
            'EntryPostStatusChange' => [
                static fn(): object => new EntryPostStatusChange('contact', 9, $change),
                ['form' => 'contact', 'id' => 9],
            ],
            'EntryPostRedact'       => [
                static fn(): object => new EntryPostRedact('contact', [1, 2]),
                ['form' => 'contact', 'ids' => [1, 2]],
            ],
            'CachePreClear'         => [static fn(): object => new CachePreClear('article'), ['scope' => 'article']],
            'CachePostClear'        => [static fn(): object => new CachePostClear('article'), ['scope' => 'article']],
            'MigrationsPostRun'     => [
                static fn(): object => new MigrationsPostRun(Target::Site, ['Version1']),
                [
                    'target'  => Target::Site,
                    'applied' => ['Version1'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{Closure(): object}>
     */
    public static function stoppedPostEventProvider(): array
    {
        $change = self::change();
        $why    = [Outcome::Stopped, 'Stopped by seo'];

        return [
            'RecordPostSave'        => [static fn(): object => new RecordPostSave('article', 5, $change, ...$why)],
            'SettingsPostSave'      => [static fn(): object => new SettingsPostSave('newsletter', $change, ...$why)],
            'PermissionsPostChange' => [static fn(): object => new PermissionsPostChange('editor', $change, ...$why)],
            'AssetPostUpload'       => [static fn(): object => new AssetPostUpload('a/b.jpg', ...$why)],
            'AssetPostDelete'       => [static fn(): object => new AssetPostDelete('a/b.jpg', ...$why)],
            'UserPostSignIn'        => [static fn(): object => new UserPostSignIn('ann', '10.0.0.1', ...$why)],
            'EntryPostSubmit'       => [static fn(): object => new EntryPostSubmit('contact', null, $change, ...$why)],
            'EntryPostStatusChange' => [
                static fn(): object => new EntryPostStatusChange('contact', 9, $change, ...$why),
            ],
            'CachePostClear'        => [static fn(): object => new CachePostClear('all', ...$why)],
        ];
    }

    private static function change(): Change
    {
        return new Change(['title' => 'Old'], ['title' => 'New']);
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[Test]
    #[DataProvider('payloadProvider')]
    public function carriesItsSubject(Closure $event, array $payload): void
    {
        static::assertSame($payload, get_object_vars($event()));
    }

    #[Test]
    #[DataProvider('changeEventProvider')]
    public function carriesTheChange(Closure $make): void
    {
        $event = $make();
        static::assertInstanceOf(ChangeEventInterface::class, $event);
        static::assertEquals(self::change(), $event->change());
    }

    #[Test]
    public function referencesNoRecordWithoutAnId(): void
    {
        $change = self::change();

        static::assertSame(
            [null, null],
            [
                (new RecordPreCreate('article', null, $change))->record(),
                (new RecordPostSave('article', null, $change))->record(),
            ],
        );
    }

    #[Test]
    public function referencesTheRecordOnceItHasAnId(): void
    {
        $change = self::change();

        static::assertEquals(
            [new RecordReference('article', 5), new RecordReference('article', 6), new RecordReference('article', 7)],
            [
                (new RecordPreSave('article', 5, $change))->record(),
                (new RecordSave('article', 6, $change))->record(),
                (new RecordPostSave('article', 7, $change))->record(),
            ],
        );
    }

    #[Test]
    #[DataProvider('donePostEventProvider')]
    public function reportsADoneActionByDefault(Closure $make): void
    {
        $event = $make();
        static::assertInstanceOf(PostEventInterface::class, $event);
        static::assertSame([Outcome::Done, '', true], [$event->outcome(), $event->reason(), $event->succeeded()]);
    }

    #[Test]
    public function reportsAFailedAction(): void
    {
        $event = new RecordPostSave('article', 5, self::change(), Outcome::Failed, 'Database unavailable');

        static::assertSame([Outcome::Failed, false], [$event->outcome(), $event->succeeded()]);
    }

    #[Test]
    #[DataProvider('stoppedPostEventProvider')]
    public function reportsAnActionThatDidNotGoThrough(Closure $make): void
    {
        $event = $make();
        static::assertInstanceOf(PostEventInterface::class, $event);
        static::assertSame(
            [Outcome::Stopped, 'Stopped by seo', false],
            [$event->outcome(), $event->reason(), $event->succeeded()],
        );
    }

    #[Test]
    public function reportsAnAddOnsFailureSafely(): void
    {
        $handle = new Handle('newsletter');
        $event  = new ExtensionPostFail(
            $handle,
            FailurePoint::Listener,
            'sync-subscriber',
            'The list API answered 500',
        );

        static::assertSame(
            [$handle, FailurePoint::Listener, 'sync-subscriber', Outcome::Failed, 'The list API answered 500'],
            [$event->handle, $event->point, $event->name, $event->outcome(), $event->reason()],
        );
    }

    #[Test]
    public function reportsARefusedSignInAsFailed(): void
    {
        $event = new SignInPostFail('ann', '10.0.0.1', 'Wrong password');

        static::assertSame([Outcome::Failed, 'Wrong password'], [$event->outcome(), $event->reason()]);
    }
}
