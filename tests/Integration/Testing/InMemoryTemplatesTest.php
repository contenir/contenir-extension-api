<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Integration\Testing;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Handler\Tab;
use Contenir\Extension\Api\RecordList\RecordList;
use Contenir\Extension\Api\RecordList\RecordListAction;
use Contenir\Extension\Api\RecordList\RecordListCell;
use Contenir\Extension\Api\RecordList\RecordListLink;
use Contenir\Extension\Api\RecordList\RecordListPager;
use Contenir\Extension\Api\RecordList\RecordListRow;
use Contenir\Extension\Api\Testing\InMemoryTemplates;
use LogicException;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function dirname;
use function ob_get_level;

#[Group('integration')]
final class InMemoryTemplatesTest extends TestCase
{
    private InMemoryTemplates $templates;

    #[Test]
    public function drawsAnEmptyRecordListAsItsMessage(): void
    {
        $list = new RecordList(
            ['Record'],
            [],
            new RecordListPager(1, 50, 0, static fn(int $page): string => "/p/{$page}"),
            emptyTitle: 'Nothing & all',
            emptyDescription: 'No <record>',
        );

        static::assertSame(
            '<div class="empty"><h2>Nothing &amp; all</h2><p>No &lt;record&gt;</p></div>',
            $this->templates->recordList($list),
        );
    }

    #[Test]
    public function drawsARecordListWithItsBoxesLinksAndPages(): void
    {
        $list = new RecordList(
            ['Record', 'To fix'],
            [
                new RecordListRow(
                    'site|1',
                    'Tea & cake',
                    [
                        RecordListCell::of('Tea & cake', '/edit?a=1&b=2'),
                        new RecordListCell(['No <title>', 'No image']),
                    ],
                    [new RecordListLink('View', '/view?a=1&b=2', 'eye')],
                ),
                new RecordListRow('site|2', 'Two', [RecordListCell::of('Two'), RecordListCell::of('Fine')]),
            ],
            new RecordListPager(1, 3, 6, static fn(int $page): string => "/p?page={$page}&x=1"),
            bulk: [
                new RecordListAction('Hide', 'eye-off', 'fix', 'hide'),
                new RecordListAction('Show', 'eye', 'fix', 'show'),
            ],
            bulkUrl: '/fix?a=1&b=2',
        );

        static::assertSame(
            '<form action="/fix?a=1&amp;b=2">'
                . InMemoryTemplates::FORM_FIELD
                . '<button name="fix" value="hide">Hide</button><button name="fix" value="show">Show</button></form>'
                . '<table><tr><th>Record</th><th>To fix</th></tr><tr>'
                . '<td><input type="checkbox" name="records[]" value="site|1" aria-label="Select Tea &amp; cake"></td>'
                . '<td><a href="/edit?a=1&amp;b=2">Tea &amp; cake</a></td><td>No &lt;title&gt; / No image</td>'
                . '<td><a href="/view?a=1&amp;b=2">View</a></td></tr><tr>'
                . '<td><input type="checkbox" name="records[]" value="site|2" aria-label="Select Two"></td>'
                . '<td>Two</td><td>Fine</td></tr></table>'
                . '<a href="/p?page=1&amp;x=1">1</a><a href="/p?page=2&amp;x=1">2</a>',
            $this->templates->recordList($list),
        );
    }

    #[Test]
    public function drawsARecordListWithoutBoxesOrPagesWhereItHasNeither(): void
    {
        $list = new RecordList(
            ['Record'],
            [new RecordListRow('1', 'One', [RecordListCell::of('One')])],
            new RecordListPager(1, 50, 1, static fn(int $page): string => "/p/{$page}"),
        );

        static::assertSame(
            '<table><tr><th>Record</th></tr><tr><td>One</td></tr></table>',
            $this->templates->recordList($list),
        );
    }

    #[Test]
    public function drawsATemplateWithItsValues(): void
    {
        static::assertSame("<p>Tea &amp; cake</p>\n", $this->templates->render('home', ['note' => 'Tea & cake']));
    }

    #[Test]
    public function drawsItsTabsAsLinksWithTheCurrentOneMarked(): void
    {
        $templates = new InMemoryTemplates(new Handle('fixture'), '/nowhere', [
            new Tab('Notes & lists', 'notes', ['page' => 2, 'sort' => 'title']),
            new Tab('Settings', 'settings', current: true),
        ]);

        static::assertSame(
            '<nav class="tabs">'
                . '<a href="/admin/extension/fixture/notes?page=2&amp;sort=title">Notes &amp; lists</a>'
                . '<a href="/admin/extension/fixture/settings" aria-current="page">Settings</a>'
                . '</nav>',
            $templates->tabs(),
        );
    }

    #[Test]
    public function drawsNoTabsForATemplateWithout(): void
    {
        static::assertSame('', $this->templates->tabs());
    }

    #[Test]
    public function escapesTextAndQuotes(): void
    {
        static::assertSame(
            '&lt;a href=&quot;x&quot;&gt;Tom&apos;s&lt;/a&gt;',
            $this->templates->escape('<a href="x">Tom\'s</a>'),
        );
    }

    #[Test]
    public function givesAHandlersUrlWithItsQuery(): void
    {
        static::assertSame(
            ['/admin/extension/fixture/entries', '/admin/extension/fixture/entries?form=4&status=new'],
            [$this->templates->url('entries'), $this->templates->url('entries', ['form' => 4, 'status' => 'new'])],
        );
    }

    #[Test]
    public function givesAnAssetsUrlUnderTheModule(): void
    {
        static::assertSame(
            ['/cms/ext/fixture/js/editor.js', '/cms/ext/fixture/css/fixture.css'],
            [$this->templates->asset('js/editor.js'), $this->templates->asset('/css/fixture.css')],
        );
    }

    #[Test]
    public function linksAPageForTheRecordsLeftOver(): void
    {
        $list = new RecordList(
            ['Record'],
            [new RecordListRow('1', 'One', [RecordListCell::of('One')])],
            new RecordListPager(1, 3, 7, static fn(int $page): string => "/{$page}"),
        );

        static::assertStringEndsWith(
            '</table><a href="/1">1</a><a href="/2">2</a><a href="/3">3</a>',
            $this->templates->recordList($list),
        );
    }

    #[Test]
    public function namesAnIconInAnEmptySvg(): void
    {
        static::assertSame(
            '<svg class="icon" data-icon="trash&quot;" aria-hidden="true"></svg>',
            $this->templates->icon('trash"'),
        );
    }

    #[Test]
    public function printsAFixedToken(): void
    {
        static::assertSame(InMemoryTemplates::FORM_FIELD, $this->templates->token());
    }

    #[Test]
    public function refusesAMalformedTemplateName(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        $this->templates->render('../home');
    }

    #[Test]
    public function refusesATemplateTheModuleDoesNotHave(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Module "fixture" has no template "missing"');

        $this->templates->render('missing');
    }

    #[Test]
    public function throwsWhatATemplateThrowsAndDropsItsOutput(): void
    {
        $level = ob_get_level();

        try {
            $this->templates->render('boom');
            static::fail('The template should have thrown');
        } catch (RuntimeException $failure) {
            static::assertSame(['The template broke', $level], [$failure->getMessage(), ob_get_level()]);
        }
    }

    #[Override]
    protected function setUp(): void
    {
        $this->templates = new InMemoryTemplates(
            new Handle('fixture'),
            dirname(__DIR__, levels: 2) . '/TestAsset/Module/templates',
        );
    }
}
