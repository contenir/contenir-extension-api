<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Field;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Field\FieldDisplayLine;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class FieldDisplayLineTest extends TestCase
{
    #[Test]
    public function holdsALabelItsTextAndAnOptionalNote(): void
    {
        $plain = new FieldDisplayLine('Description', 'A house.');
        $noted = new FieldDisplayLine('Description', 'A house.', 'From content');

        static::assertSame(
            [
                ['Description', 'A house.', null],
                ['Description', 'A house.', 'From content'],
            ],
            [[$plain->label, $plain->text, $plain->note], [$noted->label, $noted->text, $noted->note]],
        );
    }

    #[Test]
    public function refusesAnEmptyLabel(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('A field display label must not be empty');

        new FieldDisplayLine(' ', 'A house.');
    }

    #[Test]
    public function refusesAnEmptyText(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('A field display text must not be empty');

        new FieldDisplayLine('Description', ' ');
    }
}
