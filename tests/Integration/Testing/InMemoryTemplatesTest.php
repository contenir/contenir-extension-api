<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Integration\Testing;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Handler\Tab;
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
