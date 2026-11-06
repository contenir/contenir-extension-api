<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Panel;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Panel\Link;
use Contenir\Extension\Api\Panel\Panel;
use Contenir\Extension\Api\Panel\PanelImage;
use Contenir\Extension\Api\Panel\PanelPreview;
use Contenir\Extension\Api\Panel\PanelRow;
use Contenir\Extension\Api\Panel\RecordView;
use Contenir\Extension\Api\Tests\TestAsset\Extension\SubscriberPanel;
use Contenir\Extension\Api\Url\RecordReference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class PanelTest extends TestCase
{
    /**
     * @return array<string, array{callable(): object, string}>
     */
    public static function emptyProvider(): array
    {
        return [
            'a panel title'    => [static fn(): Panel => new Panel(' '), 'A panel title must not be empty'],
            'a link label'     => [static fn(): Link => new Link('', '/x'), 'A panel link label must not be empty'],
            'a link URL'       => [static fn(): Link => new Link('Open', ''), 'A panel link URL must not be empty'],
            'an image URL'     => [
                static fn(): PanelImage => new PanelImage(' '),
                'A panel image URL must not be empty',
            ],
            'a row label'      => [static fn(): PanelRow => new PanelRow(''), 'A panel row label must not be empty'],
            'a record section' => [
                static fn(): RecordView => new RecordView('', 1),
                'A record view section must not be empty',
            ],
        ];
    }

    #[Test]
    public function aPanelSeesTheRecordItIsShownBeside(): void
    {
        $panel = (new SubscriberPanel())->panel(
            new RecordView('library.people.members', 7, ['email' => 'ann@example.com']),
            static::createStub(ExtensionContextInterface::class),
        );

        static::assertSame(['ann@example.com is subscribed.'], $panel?->paragraphs);
    }

    #[Test]
    public function aPanelWithOnlyATitleParagraphsAndLinksHasNoImageRowsOrMenu(): void
    {
        $panel = new Panel('Newsletter', ['Subscribed.'], [new Link('Open', '/x')]);

        static::assertSame([null, [], [], null], [$panel->image, $panel->rows, $panel->menu, $panel->preview]);
    }

    #[Test]
    public function aPreviewHoldsATitleAndDescriptionEitherOfWhichMayBeMissing(): void
    {
        $preview = new PanelPreview('The house', 'At dusk');
        $panel   = new Panel('SEO', preview: $preview);
        $empty   = new PanelPreview();

        static::assertSame(
            [$preview, 'The house', 'At dusk', null, null],
            [$panel->preview, $preview->title, $preview->description, $empty->title, $empty->description],
        );
    }

    #[Test]
    public function aRowNeedsNeitherADetailNorAFixLink(): void
    {
        $row = new PanelRow('Share image');

        static::assertSame(['', null], [$row->detail, $row->fix]);
    }

    #[Test]
    public function describesAPanel(): void
    {
        $link  = new Link('Open the list', '/admin/extension/newsletter/subscribers');
        $panel = new Panel('Newsletter', ['Subscribed.'], [$link]);

        static::assertSame(
            ['Newsletter', ['Subscribed.'], [$link], 'Open the list', '/admin/extension/newsletter/subscribers'],
            [$panel->title, $panel->paragraphs, $panel->links, $link->label, $link->url],
        );
    }

    #[Test]
    public function describesAPanelWithAnImageRowsAndAMenu(): void
    {
        $image = new PanelImage('/asset/share.jpg', 'The house at dusk');
        $fix   = new Link('Fix', '/admin/records/12');
        $row   = new PanelRow('Description', 'Missing', $fix);
        $menu  = [new Link('Hide from search', '/admin/hide/12')];
        $panel = new Panel('SEO', image: $image, rows: [$row], menu: $menu);

        static::assertSame(
            [$image, [$row], $menu, 'Description', 'Missing', $fix, 'The house at dusk'],
            [$panel->image, $panel->rows, $panel->menu, $row->label, $row->detail, $row->fix, $image->alt],
        );
    }

    #[Test]
    public function describesARecord(): void
    {
        $record = new RecordView('library.people.members', 7, ['email' => 'ann@example.com']);

        static::assertEquals(
            [
                'library.people.members',
                7,
                ['email' => 'ann@example.com'],
                new RecordReference('library.people.members', 7),
            ],
            [$record->section, $record->id, $record->values, $record->reference()],
        );
    }

    /**
     * @param callable(): object $make
     */
    #[Test]
    #[DataProvider('emptyProvider')]
    public function rejectsAnEmptyPart(callable $make, string $message): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage($message);

        $make();
    }

    #[Test]
    public function showsNothingByDefault(): void
    {
        static::assertSame([[], []], [(new Panel('Newsletter'))->paragraphs, (new Panel('Newsletter'))->links]);
    }
}
