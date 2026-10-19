<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Internal;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Values;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ValuesTest extends TestCase
{
    #[Test]
    public function failsWhenARequiredStringIsMissing(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('The declaration has no "name" string');

        Values::required([], 'name');
    }

    #[Test]
    public function failsWhenARequiredValueIsNotAString(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        Values::required(['name' => 5], 'name');
    }

    #[Test]
    public function fallsBackWhenAStringIsMissingOrNotAString(): void
    {
        static::assertSame(
            ['banner', 'banner', ''],
            [
                Values::string([], 'region', 'banner'),
                Values::string(['region' => 1], 'region', 'banner'),
                Values::string([], 'region'),
            ],
        );
    }

    #[Test]
    public function readsABoolean(): void
    {
        static::assertSame(
            [true, false, false, true],
            [
                Values::bool(['confirm' => true], 'confirm'),
                Values::bool([], 'confirm'),
                Values::bool(['confirm' => 1], 'confirm'),
                Values::bool([], 'confirm', true),
            ],
        );
    }

    #[Test]
    public function readsANullableString(): void
    {
        static::assertSame(
            ['Sure?', null, null],
            [
                Values::nullableString(['m' => 'Sure?'], 'm'),
                Values::nullableString([], 'm'),
                Values::nullableString(['m' => true], 'm'),
            ],
        );
    }

    #[Test]
    public function readsARequiredString(): void
    {
        static::assertSame('sync', Values::required(['name' => 'sync'], 'name'));
    }

    #[Test]
    public function readsAString(): void
    {
        static::assertSame('aside', Values::string(['region' => 'aside'], 'region', 'banner'));
    }
}
