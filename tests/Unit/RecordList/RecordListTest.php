<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\RecordList;

use Closure;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\RecordList\RecordList;
use Contenir\Extension\Api\RecordList\RecordListAction;
use Contenir\Extension\Api\RecordList\RecordListBadge;
use Contenir\Extension\Api\RecordList\RecordListCell;
use Contenir\Extension\Api\RecordList\RecordListLink;
use Contenir\Extension\Api\RecordList\RecordListPager;
use Contenir\Extension\Api\RecordList\RecordListRow;
use Contenir\Extension\Api\RecordList\RecordListTone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class RecordListTest extends TestCase
{
    /**
     * @return array<string, array{Closure(): mixed}>
     */
    public static function refused(): array
    {
        return [
            'an empty heading'         => [static fn(): RecordList => new RecordList([' '], [], self::pager())],
            'an empty empty title'     => [
                static fn(): RecordList => new RecordList(['Record'], [], self::pager(), emptyTitle: ' '),
            ],
            'an empty bulk field'      => [
                static fn(): RecordList => new RecordList(['Record'], [], self::pager(), selectName: ' '),
            ],
            'too few cells'            => [
                static fn(): RecordList => new RecordList(
                    ['Record'],
                    [new RecordListRow('1', 'One', [])],
                    self::pager(),
                ),
            ],
            'bulk with nowhere to go'  => [
                static fn(): RecordList => new RecordList(
                    ['Record'],
                    [],
                    self::pager(),
                    bulk: [new RecordListAction('Hide', 'eye-off', 'fix', 'hide')],
                ),
            ],
            'a row without an id'      => [static fn(): RecordListRow => new RecordListRow(' ', 'One', [])],
            'a row without a label'    => [static fn(): RecordListRow => new RecordListRow('1', ' ', [])],
            'a link without a label'   => [static fn(): RecordListLink => new RecordListLink(' ', '/a', 'eye')],
            'a link without a URL'     => [static fn(): RecordListLink => new RecordListLink('View', ' ', 'eye')],
            'a link without an icon'   => [static fn(): RecordListLink => new RecordListLink('View', '/a', ' ')],
            'an action without label'  => [
                static fn(): RecordListAction => new RecordListAction(' ', 'eye', 'fix', 'x'),
            ],
            'an action without icon'   => [
                static fn(): RecordListAction => new RecordListAction('Hide', ' ', 'fix', 'x'),
            ],
            'an action without a name' => [
                static fn(): RecordListAction => new RecordListAction('Hide', 'eye', ' ', 'x'),
            ],
            'page zero'                => [static fn(): RecordListPager => self::pager(page: 0)],
            'a page size of zero'      => [static fn(): RecordListPager => self::pager(perPage: 0)],
            'a negative total'         => [static fn(): RecordListPager => self::pager(total: -1)],
            'an empty badge label'     => [
                static fn(): RecordListBadge => new RecordListBadge(' ', RecordListTone::Info),
            ],
            'a badge short of a line'  => [
                static fn(): RecordListCell => new RecordListCell(['One', 'Two'], badges: [null]),
            ],
        ];
    }

    private static function pager(int $page = 1, int $perPage = 50, int $total = 0): RecordListPager
    {
        return new RecordListPager($page, $perPage, $total, static fn(int $page): string => "/p/{$page}");
    }

    #[Test]
    public function aCellHasNoBadgesUnlessGivenSome(): void
    {
        static::assertSame([], (new RecordListCell(['One']))->badges);
    }

    #[Test]
    public function aCellHoldsABadgeForEachLineOrNone(): void
    {
        $high = new RecordListBadge('High', RecordListTone::Danger);
        $cell = new RecordListCell(['One', 'Two'], badges: [$high, null]);

        static::assertSame([
            [$high,  null],
            ['High', RecordListTone::Danger],
        ], [$cell->badges, [$high->label, $high->tone]]);
    }

    #[Test]
    public function aCellIsNotMutedUnlessAsked(): void
    {
        static::assertSame(
            [false, false, null],
            [
                RecordListCell::of('Home')->muted,
                (new RecordListCell(['Home']))->muted,
                RecordListCell::of('Home')->href,
            ],
        );
    }

    #[Test]
    public function aCellOfOneLineHoldsItsTextLinkAndTone(): void
    {
        $cell = RecordListCell::of('Home', '/edit', muted: true);

        static::assertSame([['Home'], '/edit', true], [$cell->lines, $cell->href, $cell->muted]);
    }

    #[Test]
    public function aLinkStaysInTheTabUnlessAsked(): void
    {
        static::assertFalse((new RecordListLink('View', '/a', 'eye'))->newTab);
    }

    #[Test]
    public function aListNeedsNoBulkUrlWithoutBulkActions(): void
    {
        $list = new RecordList(['Record'], [], self::pager());

        static::assertSame(
            ['No records', 'Nothing here matches. Try other filters.', [], '', [], 'records'],
            [
                $list->emptyTitle,
                $list->emptyDescription,
                $list->bulk,
                $list->bulkUrl,
                $list->bulkValues,
                $list->selectName,
            ],
        );
    }

    #[Test]
    public function aPagerAllowsTheSmallestValues(): void
    {
        $pager = self::pager(
            page: 1,
            perPage: 1,
            total: 0,
        );

        static::assertSame([1, 1, 0, '/p/4'], [$pager->page, $pager->perPage, $pager->total, ($pager->pageUrl)(4)]);
    }

    /**
     * @param Closure(): mixed $make
     */
    #[Test]
    #[DataProvider('refused')]
    public function refusesWhatTheHostCouldNotDraw(Closure $make): void
    {
        $this->expectException(InvalidDeclarationException::class);

        $make();
    }

    #[Test]
    public function saysWhichRowHasTheWrongNumberOfCells(): void
    {
        $this->expectExceptionMessage('Record list row "7" has 0 cells for 2 headings');

        new RecordList(['Record', 'Section'], [new RecordListRow('7', 'Seven', [])], self::pager());
    }
}
