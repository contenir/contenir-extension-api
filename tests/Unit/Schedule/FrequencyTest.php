<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Schedule;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Schedule\Frequency;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class FrequencyTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function badCronProvider(): array
    {
        return [
            'four fields' => ['* * * *'],
            'six fields'  => ['* * * * * *'],
            'empty'       => [''],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function cronProvider(): array
    {
        return [
            'every minute'    => ['* * * * *', '* * * * *'],
            'steps and lists' => ['*/15 0,12 1-5 JAN mon-fri', '*/15 0,12 1-5 JAN mon-fri'],
            'padded'          => ["  0 3 * * *\n", '0 3 * * *'],
            'tabs between'    => ["0\t3 * * *", "0\t3 * * *"],
        ];
    }

    #[Test]
    public function rejectsAFieldWithALeadingSymbol(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        Frequency::cron(';5 * * * *');
    }

    #[Test]
    public function rejectsAFieldWithATrailingSymbol(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        Frequency::cron('5; * * * *');
    }

    #[Test]
    public function rejectsAnInvalidField(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Cron expression "* * * * ;" has an invalid field ";"');

        Frequency::cron('* * * * ;');
    }

    #[Test]
    public function rejectsLessThanAMinute(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('A schedule must run at most once a minute, not every 0');

        Frequency::everyMinutes(0);
    }

    #[Test]
    #[DataProvider('badCronProvider')]
    public function rejectsTheWrongNumberOfFields(string $expression): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage("Cron expression \"{$expression}\" must have five fields");

        Frequency::cron($expression);
    }

    #[Test]
    public function runsAsOftenAsEveryMinute(): void
    {
        static::assertSame(1, Frequency::everyMinutes(1)->minutes);
    }

    #[Test]
    public function runsEverySoManyMinutes(): void
    {
        $frequency = Frequency::everyMinutes(15);

        static::assertSame([15, null], [$frequency->minutes, $frequency->cron]);
    }

    #[Test]
    #[DataProvider('cronProvider')]
    public function runsOnACronExpression(string $expression, string $kept): void
    {
        $frequency = Frequency::cron($expression);

        static::assertSame([null, $kept], [$frequency->minutes, $frequency->cron]);
    }
}
