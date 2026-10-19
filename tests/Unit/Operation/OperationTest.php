<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Operation;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Operation\Operation;
use Contenir\Extension\Api\Tests\TestAsset\Extension\PurgeHandler;
use Contenir\Extension\Api\Tests\TestAsset\Extension\ResyncTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class OperationTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function requiredKeyProvider(): array
    {
        return [
            'name'      => ['name'],
            'label'     => ['label'],
            'icon'      => ['icon'],
            'privilege' => ['privilege'],
            'handler'   => ['handler'],
        ];
    }

    #[Test]
    public function failsWhenTheHandlerIsNotAnOperationHandler(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        Operation::fromArray([
            'name'      => 'purge',
            'label'     => 'Purge',
            'icon'      => 'trash',
            'privilege' => 'purge',
            'handler'   => ResyncTask::class,
        ]);
    }

    #[Test]
    #[DataProvider('requiredKeyProvider')]
    public function failsWithoutARequiredKey(string $key): void
    {
        $data = [
            'name'      => 'purge',
            'label'     => 'Purge',
            'icon'      => 'trash',
            'privilege' => 'purge',
            'handler'   => PurgeHandler::class,
        ];
        unset($data[$key]);

        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage("The declaration has no \"{$key}\" string");

        Operation::fromArray($data);
    }

    #[Test]
    public function fillsDefaultsWhenReadingAnArray(): void
    {
        $operation = Operation::fromArray([
            'name'      => 'purge',
            'label'     => 'Purge',
            'icon'      => 'trash',
            'privilege' => 'purge',
            'handler'   => PurgeHandler::class,
        ]);

        static::assertEquals(new Operation('purge', 'Purge', 'trash', 'purge', PurgeHandler::class), $operation);
    }

    #[Test]
    public function roundTripsThroughAnArray(): void
    {
        $operation = new Operation(
            name: 'purge',
            label: 'Purge',
            icon: 'trash',
            privilege: 'purge',
            handler: PurgeHandler::class,
            region: 'aside',
            surface: 'view',
            httpMethod: 'GET',
            confirm: true,
            confirmMessage: 'Sure?',
        );

        static::assertEquals($operation, Operation::fromArray($operation->toArray()));
    }

    #[Test]
    public function writesEveryKey(): void
    {
        $operation = new Operation('purge', 'Purge', 'trash', 'purge', PurgeHandler::class);

        static::assertSame(
            [
                'name'           => 'purge',
                'label'          => 'Purge',
                'icon'           => 'trash',
                'privilege'      => 'purge',
                'handler'        => PurgeHandler::class,
                'region'         => 'banner',
                'surface'        => 'update',
                'httpMethod'     => 'POST',
                'confirm'        => false,
                'confirmMessage' => null,
            ],
            $operation->toArray(),
        );
    }
}
