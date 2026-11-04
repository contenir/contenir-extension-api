<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Section;

use Contenir\Extension\Api\Event\Record\RecordPreSave;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Exception\RowsRefusedException;
use Contenir\Extension\Api\Record\RecordRow;
use Contenir\Extension\Api\Record\RecordStatus;
use Contenir\Extension\Api\Section\Element;
use Contenir\Extension\Api\Section\ImpliedListener;
use Contenir\Extension\Api\Section\SectionBlock;
use Contenir\Extension\Api\Url\RecordReference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[Group('unit')]
final class SectionPartsTest extends TestCase
{
    /**
     * @return array<string, array{string, string, list<string>, string}>
     */
    public static function badListenerProvider(): array
    {
        return [
            'malformed name'  => ['Check', RecordPreSave::class, [], 'Listener name "Check"'],
            'not an event'    => ['check-meta', stdClass::class, [], 'must implement'],
            'malformed field' => ['check-meta', RecordPreSave::class, ['Meta Title'], 'Meta Title'],
        ];
    }

    #[Test]
    public function aBlockIsOnAnUnlinkableSectionWithNoFieldsUnlessItSays(): void
    {
        $block = new SectionBlock('library.studio.project', new Element('seo'));

        static::assertSame([false, []], [$block->linkable, $block->fields]);
    }

    #[Test]
    public function aBlockKnowsTheFieldsOfItsSection(): void
    {
        $block = new SectionBlock('library.studio.project', new Element('seo'), ['title', 'meta_title']);

        static::assertSame([true, false], [$block->hasField('meta_title'), $block->hasField('abstract')]);
    }

    #[Test]
    public function aBlockNeedsASection(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('A section block section must not be empty');

        new SectionBlock('', new Element('seo'));
    }

    /**
     * @param class-string<\Contenir\Extension\Api\Event\EventInterface> $event
     * @param list<string>                                               $fields
     */
    #[Test]
    #[DataProvider('badListenerProvider')]
    public function anImpliedListenerIsChecked(string $name, string $event, array $fields, string $message): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage($message);

        new ImpliedListener($name, $event, $fields);
    }

    #[Test]
    public function anImpliedListenerKeepsItsScopeUnlessItNarrowsIt(): void
    {
        $listener = new ImpliedListener('check-meta', RecordPreSave::class);

        static::assertSame(['check-meta', RecordPreSave::class, []], [
            $listener->listener,
            $listener->event,
            $listener->fields,
        ]);
    }

    #[Test]
    public function aRowGivesAColumnsValueOrNothing(): void
    {
        $row = new RecordRow(new RecordReference('project', 4), RecordStatus::Draft, [
            'title'   => 'House',
            'visible' => false,
            'image'   => null,
        ]);

        static::assertSame(['House', false, null, null], [
            $row->value('title'),
            $row->value('visible'),
            $row->value('image'),
            $row->value('abstract'),
        ]);
    }

    #[Test]
    public function refusedRowsSayWhy(): void
    {
        static::assertSame(
            [
                'The block on project does not read column "password"',
                'Section project has no block of add-on "seo"',
            ],
            [
                RowsRefusedException::column('project', 'password')->getMessage(),
                RowsRefusedException::section('seo', 'project')->getMessage(),
            ],
        );
    }
}
