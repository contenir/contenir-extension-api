<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Testing\Internal;

use Contenir\Extension\Api\Exception\BlockNotSavedException;
use Contenir\Extension\Api\Exception\RowsRefusedException;
use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Record\RecordRow;
use Contenir\Extension\Api\Section\Element;
use Contenir\Extension\Api\Testing\Internal\AddOnSections;
use Contenir\Extension\Api\Testing\Internal\NoSections;
use Contenir\Extension\Api\Testing\Internal\Records;
use Contenir\Extension\Api\Testing\Internal\SectionBlocks;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;
use function count;
use function range;

#[Group('unit')]
final class AddOnSectionsTest extends TestCase
{
    private const string SECTION = 'project';

    private Records $records;

    private SectionBlocks $blocks;

    private AddOnSections $sections;

    /**
     * @return array<string, array{int, int}>
     */
    public static function negativeProvider(): array
    {
        return [
            'limit'  => [-1, 0],
            'offset' => [100, -1],
        ];
    }

    #[Test]
    public function anAddOnWithNoSectionBlocksRefusesASave(): void
    {
        $this->expectException(BlockNotSavedException::class);
        $this->expectExceptionMessage('does not save its configuration');

        (new NoSections())->save(self::SECTION, new Element('seo'), '');
    }

    #[Test]
    public function anAddOnWithNoSectionBlocksSavesNothing(): void
    {
        $none = new NoSections();

        static::assertSame(['', false], [$none->version(), $none->saves()]);
    }

    #[Test]
    public function anArrayValueReadsAsItsJsonAndAnythingElseAsNothing(): void
    {
        $this->records->put(self::SECTION, 1, ['seo' => ['robots' => 'noindex'], 'other' => new Element('x')]);

        static::assertSame(
            ['seo' => '{"robots":"noindex"}', 'other' => null],
            $this->sections->rows(self::SECTION, ['seo', 'other'])[0]->values,
        );
    }

    #[Test]
    #[DataProvider('negativeProvider')]
    public function aNegativeLimitOrOffsetIsRefused(int $limit, int $offset): void
    {
        $this->expectException(RowsRefusedException::class);
        $this->expectExceptionMessage('Rows need a limit and offset of 0 or more');

        $this->sections->rows(self::SECTION, ['title'], limit: $limit, offset: $offset);
    }

    #[Test]
    public function anOverriddenBlockKeepsItsSectionsFieldsAndWhetherItIsLinkable(): void
    {
        $this->sections->override(self::SECTION, new Element('seo'), '0');
        $block = $this->sections->block(self::SECTION);

        static::assertSame([['title'], true], [$block?->fields, $block?->linkable]);
    }

    #[Test]
    public function anOverriddenBlockReadsOnlyColumnsThatAreFieldsOfTheSection(): void
    {
        $this->sections->override(self::SECTION, new Element('seo'), '0');
        $this->seed(1);

        static::assertSame(['title' => 'Project 1'], $this->sections->rows(self::SECTION, ['title'])[0]->values);
    }

    #[Test]
    public function anOverriddenBlockRefusesAColumnThatIsNotAFieldOfTheSection(): void
    {
        $this->sections->override(self::SECTION, new Element('seo'), '0');

        $this->expectException(RowsRefusedException::class);
        $this->expectExceptionMessage('The block on project does not read column "other"');

        $this->sections->rows(self::SECTION, ['other']);
    }

    #[Test]
    public function anOverriddenBlockRefusesAFieldOfTheSectionItDoesNotRead(): void
    {
        $this->blocks->put(self::SECTION, new Element('seo'), ['title', 'body']);
        $this->sections->override(self::SECTION, new Element('seo'), '0');

        $this->expectException(RowsRefusedException::class);
        $this->expectExceptionMessage('The block on project does not read column "body"');

        $this->sections->rows(self::SECTION, ['body']);
    }

    #[Test]
    public function anOverrideChangesTheOverridesVersionAndNotTheConfigurations(): void
    {
        $this->sections->override(self::SECTION, new Element('seo'), '0');

        static::assertSame(['1', '1'], [$this->sections->overridesVersion(), $this->sections->version()]);
    }

    #[Test]
    public function anOverrideIsKeptForItsAddOnAlone(): void
    {
        $this->blocks->put(self::SECTION, new Element('other', ['schema' => 'Article']), ['title']);
        $this->sections->override(self::SECTION, new Element('seo'), '0');
        $other = $this->sectionsOf('other');

        static::assertSame(
            ['0', false, 'Article'],
            [
                $other->overridesVersion(),
                $other->overridden(self::SECTION),
                $other->block(self::SECTION)?->element->attribute('schema'),
            ],
        );
    }

    #[Test]
    public function anOverrideStandsInForTheBlockAndLeavesTheDeclaredOne(): void
    {
        $this->blocks->put(self::SECTION, new Element('seo', ['schema' => 'Article']), ['title']);
        $this->sections->override(self::SECTION, new Element('seo', ['schema' => 'Product']), '0');

        static::assertSame(
            ['Product', 'Product', 'Article', true],
            [
                $this->sections->block(self::SECTION)?->element->attribute('schema'),
                $this->sections->blocks()[0]->element->attribute('schema'),
                $this->sections->declared(self::SECTION)?->element->attribute('schema'),
                $this->sections->overridden(self::SECTION),
            ],
        );
    }

    #[Test]
    public function aRestoreChangesTheOverridesVersionOnlyWhereThereWasAnOverride(): void
    {
        $this->sections->restore(self::SECTION, '0');
        $untouched = $this->sections->overridesVersion();
        $this->sections->override(self::SECTION, new Element('seo'), '0');
        $this->sections->restore(self::SECTION, '1');

        static::assertSame(['0', '2'], [$untouched, $this->sections->overridesVersion()]);
    }

    #[Test]
    public function aRestoreReturnsTheBlockToTheDeclaredOne(): void
    {
        $this->blocks->put(self::SECTION, new Element('seo', ['schema' => 'Article']), ['title']);
        $this->sections->override(self::SECTION, new Element('seo', ['schema' => 'Product']), '0');
        $this->sections->restore(self::SECTION, '1');

        static::assertSame(
            ['Article', false],
            [
                $this->sections->block(self::SECTION)?->element->attribute('schema'),
                $this->sections->overridden(self::SECTION),
            ],
        );
    }

    #[Test]
    public function aSaveChangesTheVersion(): void
    {
        $before = $this->sections->version();
        $this->sections->save(self::SECTION, new Element('seo'), $before);

        static::assertSame(['1', '2'], [$before, $this->sections->version()]);
    }

    #[Test]
    public function aZeroLimitReadsNothing(): void
    {
        $this->seed(2);

        static::assertSame([], $this->sections->rows(self::SECTION, ['title'], limit: 0));
    }

    #[Test]
    public function idsMayBeGivenAsNumbersOrText(): void
    {
        $this->seed(3);

        static::assertSame([1, 3], $this->ids($this->sections->rows(self::SECTION, ['title'], [3, '1'])));
    }

    #[Test]
    public function noSectionsDeclareNothingAndAreNeverOverridden(): void
    {
        $none = new NoSections();
        $none->restore(self::SECTION, 'any');

        static::assertSame(
            [null, false, ''],
            [$none->declared(self::SECTION), $none->overridden(self::SECTION), $none->overridesVersion()],
        );
    }

    #[Test]
    public function noSectionsHaveNoBlocks(): void
    {
        $none = new NoSections();

        static::assertSame([[], null], [$none->blocks(), $none->block(self::SECTION)]);
    }

    #[Test]
    public function noSectionsRefuseAnOverride(): void
    {
        $this->expectException(BlockNotSavedException::class);
        $this->expectExceptionMessage('Section project has no block to override');

        (new NoSections())->override(self::SECTION, new Element('seo'), '');
    }

    #[Test]
    public function noSectionsRefuseRows(): void
    {
        $this->expectException(RowsRefusedException::class);
        $this->expectExceptionMessage('Section project has no block');

        (new NoSections())->rows(self::SECTION, ['title']);
    }

    #[Test]
    public function refusesABlockNamedAfterAnotherAddOn(): void
    {
        $this->expectException(BlockNotSavedException::class);
        $this->expectExceptionMessage('A block on project is named seo, not other');

        $this->sections->save(self::SECTION, new Element('other'), $this->sections->version());
    }

    #[Test]
    public function refusesAnOverrideAskedAgainstOverridesThatHaveMovedOn(): void
    {
        $this->expectException(BlockNotSavedException::class);
        $this->expectExceptionMessage('overrides of the add-on\'s blocks have changed');

        $this->sections->override(self::SECTION, new Element('seo'), 'stale');
    }

    #[Test]
    public function refusesAnOverrideNamedAfterAnotherAddOn(): void
    {
        $this->expectException(BlockNotSavedException::class);
        $this->expectExceptionMessage('A block on project is named seo, not other');

        $this->sections->override(self::SECTION, new Element('other'), '0');
    }

    #[Test]
    public function refusesAnOverrideOfASectionWithoutTheAddOnsBlock(): void
    {
        $this->expectException(BlockNotSavedException::class);
        $this->expectExceptionMessage('Section member has no block of add-on "seo" to override');

        $this->sections->override('member', new Element('seo'), '0');
    }

    #[Test]
    public function refusesARestoreAskedAgainstOverridesThatHaveMovedOn(): void
    {
        $this->sections->override(self::SECTION, new Element('seo'), '0');

        try {
            $this->sections->restore(self::SECTION, '0');
            static::fail('A stale restore was not refused');
        } catch (BlockNotSavedException $refused) {
            static::assertSame(
                [BlockNotSavedException::CHANGED, true],
                [$refused->reason, $this->sections->overridden(self::SECTION)],
            );
        }
    }

    #[Test]
    public function refusesASaveAskedAgainstAVersionThatHasMovedOn(): void
    {
        $this->expectException(BlockNotSavedException::class);
        $this->expectExceptionMessage('has changed since it was read');

        $this->sections->save(self::SECTION, new Element('seo'), 'stale');
    }

    #[Test]
    public function rowsReadAHundredFromTheFirstUnlessToldOtherwise(): void
    {
        $this->seed(101);

        $ids = $this->ids($this->sections->rows(self::SECTION, ['title']));

        static::assertSame([100, 1, 100], [count($ids), $ids[0], $ids[99]]);
    }

    #[Test]
    public function savesABlockReplacingTheOneTheSectionHad(): void
    {
        $this->sections->save(self::SECTION, new Element('seo', ['schema' => 'WebPage']), $this->sections->version());

        static::assertSame('WebPage', $this->sections->block(self::SECTION)?->element->attribute('schema'));
    }

    #[Test]
    public function savesWhereTheHostDoes(): void
    {
        static::assertTrue($this->sections->saves());
    }

    #[Override]
    protected function setUp(): void
    {
        $this->records = new Records();
        $this->blocks  = new SectionBlocks();
        $this->blocks->put(self::SECTION, new Element('seo'), ['title']);
        $this->sections = $this->sectionsOf('seo');
    }

    /**
     * @param list<RecordRow> $rows
     *
     * @return list<string|int>
     */
    private function ids(array $rows): array
    {
        return array_map(static fn(RecordRow $row): string|int => $row->record->id, $rows);
    }

    private function sectionsOf(string $handle): AddOnSections
    {
        return new AddOnSections(
            new Handle($handle),
            $this->blocks,
            $this->records,
            static fn(): array => ['title', 'seo', 'other'],
            static fn(): bool => true,
        );
    }

    private function seed(int $count): void
    {
        foreach (range(1, $count) as $id) {
            $this->records->put(self::SECTION, $id, ['title' => "Project {$id}"]);
        }
    }
}
