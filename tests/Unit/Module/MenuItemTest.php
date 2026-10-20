<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Module;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Module\MenuGroup;
use Contenir\Extension\Api\Module\MenuItem;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class MenuItemTest extends TestCase
{
    #[Test]
    public function groupsItemsUnderAHeading(): void
    {
        $item  = new MenuItem('Contact', 'entries', current: true);
        $group = new MenuGroup('Forms', [$item]);

        static::assertSame(['Forms', [$item]], [$group->title, $group->items]);
    }

    #[Test]
    public function isNotCurrentUnlessItSaysSo(): void
    {
        $item = new MenuItem('Contact', 'entries', ['form' => 4]);

        static::assertSame(['Contact', 'entries', ['form' => 4], false], [
            $item->label,
            $item->handler,
            $item->query,
            $item->current,
        ]);
    }

    #[Test]
    public function rejectsAMalformedHandler(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Handler name "Entries" must be');

        new MenuItem('Contact', 'Entries');
    }

    #[Test]
    public function rejectsAnEmptyLabel(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('A menu item label must not be empty');

        new MenuItem('', 'entries');
    }
}
