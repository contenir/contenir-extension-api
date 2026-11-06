<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Module;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Module\Meter;
use Contenir\Extension\Api\Module\MeterSegment;
use Contenir\Extension\Api\Module\MeterTone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class MeterTest extends TestCase
{
    /**
     * @return array<string, list<array{string, string, ?string}>>
     */
    public static function emptyTextProvider(): array
    {
        return [
            'no value'   => [['', 'Good', null]],
            'no label'   => [['90', '', null]],
            'blank note' => [['90', 'Good', '']],
        ];
    }

    #[Test]
    public function acceptsASegmentWithNoRecords(): void
    {
        static::assertSame(0, (new MeterSegment('Low impact', 0, MeterTone::Neutral))->count);
    }

    #[Test]
    public function carriesWhatItReadsWithItsSegments(): void
    {
        $segment = new MeterSegment('High impact', 3, MeterTone::Poor);
        $meter   = new Meter('72', 'Needs work', MeterTone::Warning, 'Checked 4 minutes ago', [$segment]);

        static::assertSame(['72', 'Needs work', MeterTone::Warning, 'Checked 4 minutes ago', [$segment]], [
            $meter->value,
            $meter->label,
            $meter->tone,
            $meter->caption,
            $meter->segments,
        ]);
    }

    #[Test]
    public function hasNoCaptionOrSegmentsUnlessGivenThem(): void
    {
        $meter = new Meter('72', 'Needs work', MeterTone::Warning);

        static::assertSame([null, []], [$meter->caption, $meter->segments]);
    }

    #[Test]
    public function rejectsASegmentWithANegativeCount(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage("A meter segment's count must not be negative, -1 given");

        new MeterSegment('Low impact', -1, MeterTone::Neutral);
    }

    #[Test]
    public function rejectsASegmentWithNoLabel(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        new MeterSegment('', 1, MeterTone::Neutral);
    }

    /**
     * @param array{string, string, ?string} $arguments
     */
    #[Test]
    #[DataProvider('emptyTextProvider')]
    public function rejectsEmptyText(array $arguments): void
    {
        $this->expectException(InvalidDeclarationException::class);

        new Meter($arguments[0], $arguments[1], MeterTone::Good, $arguments[2]);
    }
}
