<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Form;

use Contenir\Extension\Api\Exception\LinkRejectedException;
use Contenir\Extension\Api\Form\Link;
use Contenir\Extension\Api\Form\LinkKind;
use Contenir\Extension\Api\Record\NewRecord;
use Contenir\Extension\Api\Url\RecordReference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class LinkTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function incompleteProvider(): array
    {
        return [
            'no record'                 => [
                ['kind' => 'record', 'record' => ''],
                'Choose the page or record to link to',
            ],
            'a record not text'         => [
                ['kind' => 'record', 'record' => 1],
                'Choose the page or record to link to',
            ],
            'no section'                => [
                ['kind' => 'record', 'record' => ':4'],
                'Choose the page or record to link to',
            ],
            'no id'                     => [
                ['kind' => 'record', 'record' => 'page:'],
                'Choose the page or record to link to',
            ],
            'no colon'                  => [
                ['kind' => 'record', 'record' => 'page'],
                'Choose the page or record to link to',
            ],
            'a blank address'           => [
                ['kind' => 'address', 'address' => '  '],
                'Type or paste the address to link to',
            ],
            'a new one untitled'        => [
                ['kind' => 'new', 'section' => 'page', 'title' => ' ', 'path' => '/team'],
                'Give the new page a title and an address',
            ],
            'a new one with no address' => [
                ['kind' => 'new', 'section' => 'page', 'title' => 'Team', 'path' => ''],
                'Give the new page a title and an address',
            ],
        ];
    }

    /**
     * @return array<string, array{string|null}>
     */
    public static function noFragmentProvider(): array
    {
        return [
            'none given'        => [null],
            'nothing but space' => ['  '],
            'only the hash'     => ['#'],
        ];
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function nothingProvider(): array
    {
        return [
            'nothing posted'  => [null],
            'not an array'    => ['page:1'],
            'no kind'         => [['record' => 'page:1']],
            'an unknown kind' => [['kind' => 'event', 'record' => 'page:1']],
            'a kind not text' => [['kind' => ['record'], 'record' => 'page:1']],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function undecodableProvider(): array
    {
        return [
            'empty'           => [''],
            'no colon'        => ['record'],
            'an unknown kind' => ['event:page:1'],
            'no record id'    => ['record:page'],
            'a blank address' => ['address: '],
        ];
    }

    #[Test]
    public function decodesAnAddressItEncoded(): void
    {
        static::assertSame('tel:+61355551234', Link::decode('address:tel:+61355551234')?->address);
    }

    #[Test]
    #[DataProvider('undecodableProvider')]
    public function decodesAnythingElseAsNoLink(string $encoded): void
    {
        static::assertNull(Link::decode($encoded));
    }

    #[Test]
    public function decodesARecordItEncoded(): void
    {
        static::assertEquals(new RecordReference('page', '12'), Link::decode('record:page:12')?->record);
    }

    #[Test]
    public function decodesARecordsFragmentItEncoded(): void
    {
        $link = Link::decode('record:page:12#staff');

        static::assertEquals([new RecordReference('page', '12'), 'staff'], [$link?->record, $link?->fragment]);
    }

    #[Test]
    public function encodesAnAddressAsItsDefault(): void
    {
        static::assertSame('address:https://example.com/a', Link::address('https://example.com/a')->encode());
    }

    #[Test]
    public function encodesARecordAsItsDefault(): void
    {
        static::assertSame('record:page:12', Link::record(new RecordReference('page', 12))->encode());
    }

    #[Test]
    public function encodesARecordWithItsFragment(): void
    {
        static::assertSame('record:page:12#staff', Link::record(new RecordReference('page', 12), '#staff')->encode());
    }

    #[Test]
    public function hasNoDefaultForARecordStillToCreate(): void
    {
        static::assertNull(Link::newRecord(new NewRecord('page', 'Team', '/team'))->encode());
    }

    #[Test]
    public function keepsAColonInARecordId(): void
    {
        static::assertSame('a:b', Link::fromPosted(['kind' => 'record', 'record' => 'page:a:b'])?->record?->id);
    }

    #[Test]
    public function keepsEverythingAfterTheFirstHashAsTheFragment(): void
    {
        static::assertSame('top#more', Link::decode('record:page:12#top#more')?->fragment);
    }

    #[Test]
    #[DataProvider('noFragmentProvider')]
    public function keepsNoFragmentWhenNoneIsGiven(?string $fragment): void
    {
        $link = Link::record(new RecordReference('page', 12), $fragment);

        static::assertSame([null, 'record:page:12'], [$link->fragment, $link->encode()]);
    }

    #[Test]
    public function readsAPostedAddressTrimmed(): void
    {
        $link = Link::fromPosted(['kind' => 'address', 'address' => ' mailto:hello@example.com ']);

        static::assertSame([LinkKind::Address, 'mailto:hello@example.com'], [$link?->kind, $link?->address]);
    }

    #[Test]
    public function readsAPostedNewRecordWithItsWorkflow(): void
    {
        $link = Link::fromPosted([
            'kind'     => 'new',
            'section'  => 'page',
            'title'    => ' Our team ',
            'path'     => '/about/our-team',
            'workflow' => 'standard',
        ]);

        static::assertEquals(
            [LinkKind::NewRecord, new NewRecord('page', 'Our team', '/about/our-team', 'standard')],
            [$link?->kind, $link?->newRecord],
        );
    }

    #[Test]
    public function readsAPostedNewRecordWithoutAWorkflowAsTheSectionsDefault(): void
    {
        $link = Link::fromPosted(['kind' => 'new', 'section' => 'page', 'title' => 'Team', 'path' => '/team']);

        static::assertNull($link?->newRecord?->workflow);
    }

    #[Test]
    public function readsAPostedRecordBySectionAndId(): void
    {
        $link = Link::fromPosted(['kind' => 'record', 'record' => ' news:news-7 ']);

        static::assertEquals([LinkKind::Record, new RecordReference('news', 'news-7')], [$link?->kind, $link?->record]);
    }

    #[Test]
    #[DataProvider('nothingProvider')]
    public function readsNothingChosenAsNoLink(mixed $posted): void
    {
        static::assertNull(Link::fromPosted($posted));
    }

    /**
     * @param array<string, mixed> $posted
     */
    #[Test]
    #[DataProvider('incompleteProvider')]
    public function rejectsAnIncompleteChoiceWithAMessageForTheEditor(array $posted, string $message): void
    {
        $this->expectException(LinkRejectedException::class);
        $this->expectExceptionMessage($message);

        Link::fromPosted($posted);
    }

    #[Test]
    public function takesANewRecordsFragmentFromWhatWasPosted(): void
    {
        $link = Link::fromPosted([
            'kind'     => 'new',
            'section'  => 'page',
            'title'    => 'Team',
            'path'     => '/team',
            'fragment' => 'staff',
        ]);

        static::assertSame('staff', $link?->fragment);
    }

    #[Test]
    public function takesARecordsFragmentFromWhatWasPosted(): void
    {
        $link = Link::fromPosted(['kind' => 'record', 'record' => 'page:12', 'fragment' => ' #staff ']);

        static::assertSame('staff', $link?->fragment);
    }
}
