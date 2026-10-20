<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Handler;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Handler\Fragment;
use Contenir\Extension\Api\Handler\HandlerResult;
use Contenir\Extension\Api\Handler\ResultKind;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class FragmentTest extends TestCase
{
    #[Test]
    public function answersWithAFragmentAsOk(): void
    {
        $result = HandlerResult::fragment('builder/row', ['row' => 3]);

        static::assertEquals(
            [ResultKind::Fragment, 200, new Fragment('builder/row', ['row' => 3]), null],
            [$result->kind, $result->status, $result->fragment, $result->template],
        );
    }

    #[Test]
    public function answersWithAFragmentWithItsStatus(): void
    {
        static::assertSame(201, HandlerResult::fragment('builder/row', status: 201)->status);
    }

    #[Test]
    public function keepsItsFragmentWithAMessage(): void
    {
        static::assertEquals(
            new Fragment('builder/row'),
            HandlerResult::fragment('builder/row')->withMessage('success', 'Added')->fragment,
        );
    }

    #[Test]
    public function rejectsAMalformedName(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Template name "../row" must be');

        new Fragment('../row');
    }
}
