<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing;

use Contenir\Extension\Api\Storage\StorageProfile;
use Contenir\Storage\Adapter\InMemoryStorage;
use Contenir\Storage\StorageInterface;
use Contenir\Storage\StorageManager;

/**
 * Storage as a host lends it, held in memory, for a test to pass to
 * InMemoryHost::boot() under StorageManager's class: the site's public
 * storage as the primary profile, "local", registered first so it is the
 * primary, and the private profile. Needs
 * contenir/contenir-storage.
 *
 * @api
 */
final class MemoryStorage
{
    /**
     * @param StorageInterface|null $public  The site's storage; in memory by default
     * @param StorageInterface|null $private The private profile; in memory by default
     */
    public static function manager(?StorageInterface $public = null, ?StorageInterface $private = null): StorageManager
    {
        $manager = new StorageManager();
        $manager->register('local', $public ?? new InMemoryStorage());
        $manager->register(StorageProfile::PRIVATE, $private ?? new InMemoryStorage());

        return $manager;
    }
}
