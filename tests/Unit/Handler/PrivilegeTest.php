<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Handler;

use Contenir\Extension\Api\Handler\HandlerDeclaration;
use Contenir\Extension\Api\Handler\Privilege;
use Contenir\Extension\Api\Tests\TestAsset\Extension\ConnectHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class PrivilegeTest extends TestCase
{
    /**
     * @return array<string, array{string, Privilege}>
     */
    public static function methodProvider(): array
    {
        return [
            'GET reads'                => ['GET', Privilege::View],
            'HEAD reads'               => ['HEAD', Privilege::View],
            'lower-case get reads'     => ['get', Privilege::View],
            'POST may change things'   => ['POST', Privilege::Update],
            'DELETE may change things' => ['DELETE', Privilege::Update],
        ];
    }

    #[Test]
    #[DataProvider('methodProvider')]
    public function goesByTheMethodWhenNoneIsDeclared(string $method, Privilege $privilege): void
    {
        static::assertSame($privilege, (new HandlerDeclaration('connect', ConnectHandler::class, '/x'))->privilegeFor(
            $method,
        ));
    }

    #[Test]
    public function needsTheDeclaredPrivilegeWhateverTheMethod(): void
    {
        $declaration = new HandlerDeclaration('remove', ConnectHandler::class, '/x', Privilege::Delete);

        static::assertSame(
            [Privilege::Delete, Privilege::Delete],
            [$declaration->privilegeFor('GET'), $declaration->privilegeFor('POST')],
        );
    }
}
