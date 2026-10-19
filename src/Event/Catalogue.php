<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event;

use Contenir\Extension\Api\Event\Asset\AssetDelete;
use Contenir\Extension\Api\Event\Asset\AssetPostDelete;
use Contenir\Extension\Api\Event\Asset\AssetPostUpload;
use Contenir\Extension\Api\Event\Asset\AssetPreDelete;
use Contenir\Extension\Api\Event\Asset\AssetPreUpload;
use Contenir\Extension\Api\Event\Asset\AssetUpload;
use Contenir\Extension\Api\Event\Cache\CachePostClear;
use Contenir\Extension\Api\Event\Cache\CachePreClear;
use Contenir\Extension\Api\Event\Entry\EntryPostRedact;
use Contenir\Extension\Api\Event\Entry\EntryPostStatusChange;
use Contenir\Extension\Api\Event\Entry\EntryPostSubmit;
use Contenir\Extension\Api\Event\Entry\EntryPreStatusChange;
use Contenir\Extension\Api\Event\Entry\EntryPreSubmit;
use Contenir\Extension\Api\Event\Entry\EntryStatusChange;
use Contenir\Extension\Api\Event\Extension\ExtensionPostFail;
use Contenir\Extension\Api\Event\Migration\MigrationsPostRun;
use Contenir\Extension\Api\Event\Permissions\PermissionsChange;
use Contenir\Extension\Api\Event\Permissions\PermissionsPostChange;
use Contenir\Extension\Api\Event\Permissions\PermissionsPreChange;
use Contenir\Extension\Api\Event\Record\RecordCreate;
use Contenir\Extension\Api\Event\Record\RecordDelete;
use Contenir\Extension\Api\Event\Record\RecordDuplicate;
use Contenir\Extension\Api\Event\Record\RecordMove;
use Contenir\Extension\Api\Event\Record\RecordPostCreate;
use Contenir\Extension\Api\Event\Record\RecordPostDelete;
use Contenir\Extension\Api\Event\Record\RecordPostDuplicate;
use Contenir\Extension\Api\Event\Record\RecordPostMove;
use Contenir\Extension\Api\Event\Record\RecordPostPublish;
use Contenir\Extension\Api\Event\Record\RecordPostReorder;
use Contenir\Extension\Api\Event\Record\RecordPostSave;
use Contenir\Extension\Api\Event\Record\RecordPostStatusChange;
use Contenir\Extension\Api\Event\Record\RecordPreCreate;
use Contenir\Extension\Api\Event\Record\RecordPreDelete;
use Contenir\Extension\Api\Event\Record\RecordPreDuplicate;
use Contenir\Extension\Api\Event\Record\RecordPreMove;
use Contenir\Extension\Api\Event\Record\RecordPrePublish;
use Contenir\Extension\Api\Event\Record\RecordPreReorder;
use Contenir\Extension\Api\Event\Record\RecordPreSave;
use Contenir\Extension\Api\Event\Record\RecordPreStatusChange;
use Contenir\Extension\Api\Event\Record\RecordPublish;
use Contenir\Extension\Api\Event\Record\RecordReorder;
use Contenir\Extension\Api\Event\Record\RecordSave;
use Contenir\Extension\Api\Event\Record\RecordStatusChange;
use Contenir\Extension\Api\Event\Settings\SettingsPostSave;
use Contenir\Extension\Api\Event\Settings\SettingsPreSave;
use Contenir\Extension\Api\Event\Settings\SettingsSave;
use Contenir\Extension\Api\Event\User\SignInPostFail;
use Contenir\Extension\Api\Event\User\UserPostSignIn;
use Contenir\Extension\Api\Event\User\UserPreSignIn;

use function strrchr;
use function substr;

/**
 * Every event the CMS fires. A host fires each of them; a site names them in
 * its admin.xml bindings by their short names, such as RecordPostSave.
 *
 * @api
 */
final class Catalogue
{
    /**
     * @var list<class-string<CoreEventInterface>>
     */
    public const array EVENTS = [
        RecordCreate::class,
        RecordDelete::class,
        RecordDuplicate::class,
        RecordMove::class,
        RecordPostCreate::class,
        RecordPostDelete::class,
        RecordPostDuplicate::class,
        RecordPostMove::class,
        RecordPostPublish::class,
        RecordPostReorder::class,
        RecordPostSave::class,
        RecordPostStatusChange::class,
        RecordPreCreate::class,
        RecordPreDelete::class,
        RecordPreDuplicate::class,
        RecordPreMove::class,
        RecordPrePublish::class,
        RecordPreReorder::class,
        RecordPreSave::class,
        RecordPreStatusChange::class,
        RecordPublish::class,
        RecordReorder::class,
        RecordSave::class,
        RecordStatusChange::class,
        SettingsPostSave::class,
        SettingsPreSave::class,
        SettingsSave::class,
        AssetDelete::class,
        AssetPostDelete::class,
        AssetPostUpload::class,
        AssetPreDelete::class,
        AssetPreUpload::class,
        AssetUpload::class,
        PermissionsChange::class,
        PermissionsPostChange::class,
        PermissionsPreChange::class,
        SignInPostFail::class,
        UserPostSignIn::class,
        UserPreSignIn::class,
        EntryPostRedact::class,
        EntryPostStatusChange::class,
        EntryPostSubmit::class,
        EntryPreStatusChange::class,
        EntryPreSubmit::class,
        EntryStatusChange::class,
        CachePostClear::class,
        CachePreClear::class,
        MigrationsPostRun::class,
        ExtensionPostFail::class,
    ];

    /**
     * The core event called $name, such as RecordPostSave, or null when the
     * catalogue has none.
     *
     * @return class-string<CoreEventInterface>|null
     */
    public static function find(string $name): ?string
    {
        foreach (self::EVENTS as $class) {
            if (self::name($class) === $name) {
                return $class;
            }
        }

        return null;
    }

    /**
     * An event's short name, as admin.xml bindings use it.
     *
     * @param class-string $class
     */
    public static function name(string $class): string
    {
        $short = strrchr($class, needle: '\\');

        return false === $short ? $class : substr($short, offset: 1);
    }
}
