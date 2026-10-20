<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Handler;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Handler\Screen;
use Contenir\Extension\Api\Handler\Tab;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class TabTest extends TestCase
{
    /**
     * @return array<string, array{array{string, string, ?string}, string}>
     */
    public static function badTabProvider(): array
    {
        return [
            'an empty label'      => [[' ', 'fields', null], 'A tab label must not be empty'],
            'a malformed handler' => [['Fields', 'Fields', null], 'Handler name "Fields" must be'],
            'a malformed icon'    => [['Fields', 'fields', 'Layout List'], 'Icon name "Layout List" must be'],
        ];
    }

    #[Test]
    public function isNotCurrentAndHasNoIconUnlessItSaysSo(): void
    {
        $tab = new Tab('Entries', 'entries');

        static::assertSame([[], false, null], [$tab->query, $tab->current, $tab->icon]);
    }

    #[Test]
    public function leadsToAHandlerWithItsQueryAndIcon(): void
    {
        $tab = new Tab('Fields', 'fields', ['form' => 3], current: true, icon: 'layout-list');

        static::assertSame(
            ['Fields', 'fields', ['form' => 3], true, 'layout-list'],
            [$tab->label, $tab->handler, $tab->query, $tab->current, $tab->icon],
        );
    }

    #[Test]
    public function leavesAScreenWithoutTabsByDefault(): void
    {
        static::assertSame([], (new Screen('Settings'))->tabs);
    }

    /**
     * @param array{string, string, ?string} $tab
     */
    #[Test]
    #[DataProvider('badTabProvider')]
    public function refusesABadTab(array $tab, string $message): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage($message);

        new Tab($tab[0], $tab[1], icon: $tab[2]);
    }

    #[Test]
    public function sitsOnAScreen(): void
    {
        $tabs = [new Tab('Entries', 'entries'), new Tab('Settings', 'form', current: true)];

        static::assertSame($tabs, (new Screen('Settings', tabs: $tabs))->tabs);
    }
}
