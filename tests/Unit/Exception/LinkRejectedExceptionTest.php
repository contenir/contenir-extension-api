<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Exception;

use Contenir\Extension\Api\Exception\ExceptionInterface;
use Contenir\Extension\Api\Exception\LinkRejectedException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class LinkRejectedExceptionTest extends TestCase
{
    #[Test]
    public function carriesTheMessageForTheEditor(): void
    {
        static::assertSame('Choose a page', LinkRejectedException::because('Choose a page')->getMessage());
    }

    #[Test]
    public function isOneOfTheApisExceptions(): void
    {
        static::assertInstanceOf(ExceptionInterface::class, LinkRejectedException::because('No'));
    }
}
