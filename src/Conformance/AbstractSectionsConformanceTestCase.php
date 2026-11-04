<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance;

use Contenir\Extension\Api\Event\Extension\ExtensionPostFail;
use Contenir\Extension\Api\Event\Extension\FailurePoint;
use Contenir\Extension\Api\Record\LinkableSection;
use Contenir\Extension\Api\Record\RecordStatus;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestFactoryInterface;

use function array_column;
use function array_key_exists;
use function array_map;
use function http_build_query;
use function json_decode;

/**
 * Section blocks (Section\SectionsInterface): an element named after an
 * add-on inside a section's <extensions> reaches that add-on alone, whole and
 * uninterpreted; it reads the values of the columns its block names and no
 * others; and the listeners a block implies are bound to its section unless
 * the site writes its own line for them.
 *
 * @api
 */
abstract class AbstractSectionsConformanceTestCase extends AbstractConformanceTestCase
{
    /**
     * A section the site does not mark as linkable.
     */
    protected const string MEMBERS = 'member';

    /**
     * What lets a request reach the probe's sections handler.
     */
    private const string HANDLER = 'extension.probe.sections';

    private const string READS_TITLE_AND_EMAIL = '<probe><column>title</column><column>email</column></probe>';

    #[Test]
    public function aBlockBindsTheListenersItImplies(): void
    {
        $this->host->block(self::SECTION, '<probe><listen event="RecordPreSave">record</listen></probe>');
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame(['RecordPreSave'], $this->journal->entries());
    }

    #[Test]
    public function aBlockKeepsItsAttributesTextAndChildrenInOrder(): void
    {
        $this->host->block(
            self::SECTION,
            '<probe schema="Article"><column>title</column><note lang="en">  Said  </note><column>email</column></probe>',
        );

        static::assertSame(
            [
                'name'       => 'probe',
                'attributes' => ['schema' => 'Article'],
                'text'       => '',
                'children'   => [
                    ['name' => 'column', 'attributes' => [], 'text' => 'title', 'children' => []],
                    ['name' => 'note', 'attributes' => ['lang' => 'en'], 'text' => 'Said', 'children' => []],
                    ['name' => 'column', 'attributes' => [], 'text' => 'email', 'children' => []],
                ],
            ],
            $this->ask(['do' => 'block', 'section' => self::SECTION])['element'] ?? null,
        );
    }

    #[Test]
    public function aBlockKnowsItsSectionsFieldsAndWhetherItIsLinkable(): void
    {
        $this->host->block(self::SECTION, '<probe/>', ['title', 'email']);
        $this->host->block(self::MEMBERS, '<probe/>', ['name']);

        static::assertSame(
            [
                [self::SECTION, true, ['title', 'email']],
                [self::MEMBERS, false, ['name']],
            ],
            array_map(
                null,
                array_column($this->blocks(), 'section'),
                array_column($this->blocks(), 'linkable'),
                array_column($this->blocks(), 'fields'),
            ),
        );
    }

    #[Test]
    public function aBlockReachesOnlyTheAddOnItIsNamedAfter(): void
    {
        $this->host->block(self::SECTION, '<other><column>title</column></other>');
        $this->host->block(self::MEMBERS, '<probe/>');

        static::assertSame([self::MEMBERS], array_column($this->blocks(), 'section'));
    }

    #[Test]
    public function aFailingReaderIsReportedAndReadsNothing(): void
    {
        $this->host->block(self::SECTION, '<probe><broken/><column>title</column></probe>');
        $refused = $this->ask(['do' => 'rows', 'section' => self::SECTION, 'columns' => 'title']);

        static::assertSame(
            [true, [FailurePoint::Section, FailurePoint::Section]],
            [array_key_exists('refused', $refused ?? []), $this->failurePoints()],
        );
    }

    #[Test]
    public function aLineTheSiteWritesReplacesTheImpliedOne(): void
    {
        $this->host->block(
            self::SECTION,
            '<probe><listen event="RecordPreSave" fields="email">record</listen></probe>',
        );
        $this->bindProbe('record', 'RecordPreSave');
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame(['RecordPreSave'], $this->journal->entries());
    }

    #[Test]
    public function anImpliedListenerKeepsToItsFields(): void
    {
        $this->host->block(
            self::SECTION,
            '<probe><listen event="RecordPreSave" fields="email">record</listen></probe>',
        );
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame([], $this->journal->entries());
    }

    #[Test]
    public function anImpliedListenerKeepsToItsSection(): void
    {
        $this->host->block(self::MEMBERS, '<probe><listen event="RecordPreSave">record</listen></probe>');
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame([], $this->journal->entries());
    }

    #[Test]
    public function anImpliedListenerTheAddOnLacksIsReportedAndNotBound(): void
    {
        $this->host->block(self::SECTION, '<probe><listen event="RecordPreSave">no-such-listener</listen></probe>');

        static::assertSame([FailurePoint::Section], $this->failurePoints());
    }

    #[Test]
    public function anImpliedListenerTheSiteAlreadyBindsRunsOnce(): void
    {
        $this->bindProbe('record', 'RecordPreSave');
        $this->host->block(self::SECTION, '<probe><listen event="RecordPreSave">record</listen></probe>');
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame(['RecordPreSave'], $this->journal->entries());
    }

    #[Test]
    public function aSectionWithoutTheAddOnsBlockHasNone(): void
    {
        $this->host->block(self::SECTION, '<probe/>');

        static::assertNull($this->ask(['do' => 'block', 'section' => self::MEMBERS]));
    }

    #[Test]
    public function rowsGiveTheColumnsAskedForByIdWithEachRecordsStatus(): void
    {
        $this->host->block(self::SECTION, self::READS_TITLE_AND_EMAIL);

        static::assertSame(
            [
                [
                    'record' => self::SECTION . ':1',
                    'status' => 'published',
                    'values' => ['email' => null, 'title' => 'First'],
                ],
                [
                    'record' => self::SECTION . ':2',
                    'status' => 'draft',
                    'values' => ['email' => 'b@example.com', 'title' => 'Second'],
                ],
            ],
            $this->ask(['do' => 'rows', 'section' => self::SECTION, 'columns' => 'email,title', 'limit' => '2']),
        );
    }

    #[Test]
    public function rowsKeepToTheIdsAsked(): void
    {
        $this->host->block(self::SECTION, self::READS_TITLE_AND_EMAIL);

        static::assertSame([self::SECTION . ':3'], $this->rowRecords(['ids' => '3,99']));
    }

    #[Test]
    public function rowsKeepToTheLimitAfterTheOffset(): void
    {
        $this->host->block(self::SECTION, self::READS_TITLE_AND_EMAIL);

        static::assertSame([self::SECTION . ':2'], $this->rowRecords(['limit' => '1', 'offset' => '1']));
    }

    #[Test]
    public function rowsRefuseAColumnTheBlockDoesNotRead(): void
    {
        $this->host->block(self::SECTION, self::READS_TITLE_AND_EMAIL);

        static::assertArrayHasKey(
            'refused',
            $this->ask(['do' => 'rows', 'section' => self::SECTION, 'columns' => 'title,password']) ?? [],
        );
    }

    #[Test]
    public function rowsRefuseASectionWithoutTheAddOnsBlock(): void
    {
        $this->host->block(self::SECTION, '<other><column>title</column></other>');

        static::assertArrayHasKey(
            'refused',
            $this->ask(['do' => 'rows', 'section' => self::SECTION, 'columns' => 'title']) ?? [],
        );
    }

    /**
     * Builds the requests the tests send.
     */
    abstract protected function requests(): ServerRequestFactoryInterface;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->bootProbe();
        $this->host->linkable(new LinkableSection(self::SECTION, 'Article'));
        $this->host->seed(self::SECTION, 2, [
            'title'  => 'Second',
            'email'  => 'b@example.com',
            'status' => RecordStatus::Draft->value,
        ]);
        $this->host->seed(self::SECTION, 1, [
            'title'  => 'First',
            'status' => RecordStatus::Published->value,
        ]);
        $this->host->seed(self::SECTION, 3, [
            'title'  => 'Third',
            'status' => RecordStatus::Published->value,
        ]);
    }

    /**
     * What the probe's sections handler answered, decoded.
     *
     * @param array<string, string> $query
     *
     * @return array<array-key, mixed>|null
     */
    private function ask(array $query): ?array
    {
        $response = $this->host->handle(
            $this->requests()
                ->createServerRequest(
                    'GET',
                    'https://cms.example.com/admin/extension/probe/sections?' . http_build_query($query),
                )
                ->withQueryParams($query),
            [self::HANDLER],
        );

        /** @var array<array-key, mixed>|null */
        return json_decode((string) $response->getBody(), associative: true);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function blocks(): array
    {
        /** @var list<array<string, mixed>> */
        return $this->ask(['do' => 'blocks']) ?? [];
    }

    /**
     * @return list<FailurePoint>
     */
    private function failurePoints(): array
    {
        return array_map(
            static fn(ExtensionPostFail $failure): FailurePoint => $failure->point,
            $this->host->failures(),
        );
    }

    /**
     * The records a read of the article section's titles gave, as "<section>:<id>".
     *
     * @param array<string, string> $query
     *
     * @return list<string>
     */
    private function rowRecords(array $query): array
    {
        /** @var list<array{record: string}> $rows */
        $rows = $this->ask(['do' => 'rows', 'section' => self::SECTION, 'columns' => 'title', ...$query]) ?? [];

        return array_column($rows, 'record');
    }
}
