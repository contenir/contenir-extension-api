<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Operation;

use Contenir\Extension\Api\Operation\OperationResult;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class OperationResultTest extends TestCase
{
    #[Test]
    public function addsMessagesWithoutChangingTheOriginal(): void
    {
        $first  = OperationResult::redirectTo(['action' => 'view']);
        $second = $first->withMessage('warning', 'Partly purged');

        static::assertSame(
            [[], ['action' => 'view'], [['level' => 'warning', 'text' => 'Partly purged']]],
            [$first->messages(), $second->redirect(), $second->messages()],
        );
    }

    #[Test]
    public function appendsMessagesInOrder(): void
    {
        $result = OperationResult::message('success', 'One')->withMessage('info', 'Two');

        static::assertSame(
            [['level' => 'success', 'text' => 'One'], ['level' => 'info', 'text' => 'Two']],
            $result->messages(),
        );
    }

    #[Test]
    public function carriesAMessageWithTheDefaultRedirect(): void
    {
        $result = OperationResult::message('success', 'Purged');

        static::assertSame([null, [['level' => 'success', 'text' => 'Purged']]], [
            $result->redirect(),
            $result->messages(),
        ]);
    }

    #[Test]
    public function redirectsWithNoMessages(): void
    {
        $result = OperationResult::redirectTo(['action' => 'view']);

        static::assertSame([['action' => 'view'], []], [$result->redirect(), $result->messages()]);
    }
}
