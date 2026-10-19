<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Testing;

use Contenir\Extension\Api\Testing\ServiceMap;
use Contenir\Extension\Api\Testing\ServiceNotFoundException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[Group('unit')]
final class ServiceMapTest extends TestCase
{
    #[Test]
    public function addsServicesReplacingOnesWithTheSameId(): void
    {
        $map = (new ServiceMap(['a' => 1, 'b' => 2]))->with(['b' => 3, 'c' => 4]);

        static::assertSame([1, 3, 4, false], [$map->get('a'), $map->get('b'), $map->get('c'), $map->has('d')]);
    }

    #[Test]
    public function failsForAMissingService(): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('No service "a"');

        (new ServiceMap())->get('a');
    }

    #[Test]
    public function findsAService(): void
    {
        $service = new stdClass();

        static::assertSame([true, $service], [
            (new ServiceMap(['a' => $service]))->has('a'),
            (new ServiceMap(['a' => $service]))->get('a'),
        ]);
    }

    #[Test]
    public function holdsANullService(): void
    {
        static::assertTrue((new ServiceMap(['a' => null]))->has('a'));
    }
}
