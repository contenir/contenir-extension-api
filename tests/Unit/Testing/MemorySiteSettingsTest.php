<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Testing;

use Contenir\Extension\Api\Testing\MemorySiteSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class MemorySiteSettingsTest extends TestCase
{
    /**
     * @return array<string, array{string, string|null}>
     */
    public static function keyProvider(): array
    {
        return [
            'a top-level setting'    => ['title', 'Example'],
            'a nested setting'       => ['contact.telephone', '01234 567890'],
            'a number'               => ['contact.floor', '3'],
            'a true setting'         => ['mail.enabled', '1'],
            'a false setting'        => ['mail.debug', ''],
            'a setting holding many' => ['contact', null],
            'a setting not there'    => ['contact.fax', null],
            'below a single value'   => ['title.more', null],
            'a null setting'         => ['mail.from', null],
            'an empty key'           => ['', null],
        ];
    }

    #[Test]
    public function holdsNothingByDefault(): void
    {
        static::assertNull((new MemorySiteSettings())->get('title'));
    }

    #[Test]
    #[DataProvider('keyProvider')]
    public function readsASettingByItsDottedKey(string $key, ?string $expected): void
    {
        static::assertSame(
            $expected,
            (new MemorySiteSettings([
                'title'   => 'Example',
                'contact' => ['telephone' => '01234 567890', 'floor' => 3],
                'mail'    => ['enabled' => true, 'debug' => false, 'from' => null],
            ]))->get($key),
        );
    }
}
