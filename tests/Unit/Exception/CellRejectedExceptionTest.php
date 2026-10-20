<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Exception;

use Contenir\Extension\Api\Exception\CellRejectedException;
use Contenir\Extension\Api\Exception\ExceptionInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class CellRejectedExceptionTest extends TestCase
{
    #[Test]
    public function carriesTheMessageForTheEditor(): void
    {
        static::assertSame(
            '"Teal" is not a shade',
            CellRejectedException::because('"Teal" is not a shade')->getMessage(),
        );
    }

    #[Test]
    public function isOneOfTheApisExceptions(): void
    {
        static::assertInstanceOf(ExceptionInterface::class, CellRejectedException::because('No'));
    }
}
