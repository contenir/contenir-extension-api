<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Handler;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Form\Form;
use Contenir\Extension\Api\Handler\Screen;
use Contenir\Extension\Api\Handler\Table;
use Contenir\Extension\Api\Operation\Operation;
use Contenir\Extension\Api\Tests\TestAsset\Extension\PurgeHandler;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ScreenTest extends TestCase
{
    #[Test]
    public function describesAnEmptyTable(): void
    {
        static::assertSame([], (new Table(['email' => 'Email']))->rows);
    }

    #[Test]
    public function describesAScreen(): void
    {
        $form       = new Form();
        $table      = new Table(['email' => 'Email']);
        $operations = [new Operation('resync', 'Resync', 'sync', 'update', PurgeHandler::class)];
        $screen     = new Screen('Subscribers', 'Everyone on the list.', $form, $table, $operations);

        static::assertSame(
            ['Subscribers', 'Everyone on the list.', $form, $table, $operations],
            [$screen->title, $screen->intro, $screen->form, $screen->table, $screen->operations],
        );
    }

    #[Test]
    public function describesATable(): void
    {
        $table = new Table(['email' => 'Email', 'status' => 'Status'], [
            ['email' => 'a@example.com'],
            [
                'email'  => 'b@example.com',
                'status' => 'pending',
            ],
        ]);

        static::assertSame(
            [
                ['email' => 'Email', 'status' => 'Status'],
                [
                    ['email' => 'a@example.com'],
                    [
                        'email'  => 'b@example.com',
                        'status' => 'pending',
                    ],
                ],
            ],
            [$table->columns, $table->rows],
        );
    }

    #[Test]
    public function needsOnlyATitle(): void
    {
        $screen = new Screen('Subscribers');

        static::assertSame(['', null, null, []], [$screen->intro, $screen->form, $screen->table, $screen->operations]);
    }

    #[Test]
    public function rejectsAnEmptyTitle(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('A screen title must not be empty');

        new Screen('');
    }

    #[Test]
    public function rejectsARowWithAValueForAnUnknownColumn(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('A row has a value for "name", which is not a column');

        new Table(['email' => 'Email'], [['email' => 'a@example.com'], ['email' => 'b@example.com', 'name' => 'B']]);
    }
}
