<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Testing;

use Contenir\Extension\Api\Storage\StorageProfile;
use Contenir\Extension\Api\Testing\MemoryStorage;
use Contenir\Storage\Adapter\InMemoryStorage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class MemoryStorageTest extends TestCase
{
    #[Test]
    public function holdsTheSitesStorageAsPrimaryAndThePrivateProfile(): void
    {
        $manager = MemoryStorage::manager();

        static::assertSame(
            [['local', 'private'], 'local', true, true],
            [
                $manager->profiles(),
                $manager->primaryKey(),
                $manager->get('local') instanceof InMemoryStorage,
                $manager->get(StorageProfile::PRIVATE) instanceof InMemoryStorage,
            ],
        );
    }

    #[Test]
    public function lendsTheStorageATestGivesIt(): void
    {
        $public  = new InMemoryStorage();
        $private = new InMemoryStorage();
        $manager = MemoryStorage::manager($public, $private);

        static::assertSame(
            [$public, $private],
            [$manager->get('local'), $manager->get(StorageProfile::PRIVATE)],
        );
    }
}
