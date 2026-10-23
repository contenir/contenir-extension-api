<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Integration\Testing;

use Contenir\Extension\Api\Conformance\Probe\Journal;
use Contenir\Extension\Api\Conformance\Probe\ProbeExtension;
use Contenir\Extension\Api\Record\LinkableSection;
use Contenir\Extension\Api\Record\NewRecord;
use Contenir\Extension\Api\Record\RecordsInterface;
use Contenir\Extension\Api\Record\RecordStatus;
use Contenir\Extension\Api\Record\RecordSummary;
use Contenir\Extension\Api\Record\Workflow;
use Contenir\Extension\Api\Testing\FrozenClock;
use Contenir\Extension\Api\Testing\InMemoryHost;
use Contenir\Extension\Api\Testing\Internal\HostRecords;
use Contenir\Extension\Api\Url\RecordReference;
use DateTimeImmutable;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_intersect_key;

/**
 * The in-memory host's records, beyond what the conformance suite asks of
 * every host: what a new record stores, how stored values read, and the
 * editor it acts for outside a request.
 */
#[Group('integration')]
final class InMemoryRecordsTest extends TestCase
{
    private InMemoryHost $host;

    /**
     * @return array<string, array{array<string, string>, string}>
     */
    public static function multibyteProvider(): array
    {
        return [
            'a title' => [['title' => 'Über uns', 'path' => '/about'], 'über'],
            'a path'  => [['title' => 'Summer', 'path' => '/été'], 'ÉTÉ'],
        ];
    }

    #[Test]
    public function aBootedServiceStandsInForTheHostsRecords(): void
    {
        $records = $this->createStub(RecordsInterface::class);
        $this->host->boot(
            [ProbeExtension::HANDLE => new ProbeExtension(new Journal())],
            [ProbeExtension::HANDLE],
            [RecordsInterface::class => $records],
        );

        static::assertSame(
            $records,
            $this->host->context(ProbeExtension::HANDLE)->services()->get(RecordsInterface::class),
        );
    }

    #[Test]
    public function aCreateHeldOnTwoFieldsSaysWhyForEach(): void
    {
        $this->host->seed('page', 1, ['title' => 'About us', 'path' => '/about']);

        static::assertSame(
            ['workflow' => [HostRecords::NO_WORKFLOW], 'path' => [HostRecords::PATH_TAKEN]],
            $this->records()->create(new NewRecord('page', 'About', '/about', 'gallery'))->errors,
        );
    }

    #[Test]
    public function afterARequestTheEditorMayAddAgain(): void
    {
        $records = $this->records();
        $this->host->handle((new Psr17Factory())->createServerRequest('GET', '/admin/extension/probe/status'), []);

        static::assertTrue($records->create(new NewRecord('page', 'Contact', '/contact'))->succeeded());
    }

    #[Test]
    public function aNewRecordInASectionWithoutWorkflowsHasNone(): void
    {
        $this->host->linkable(new LinkableSection('event', 'Event', creatable: true));

        $result = $this->records()->create(new NewRecord('event', 'Open day', '/events/open-day'));

        static::assertSame(
            ['workflow' => null],
            array_intersect_key($this->host->stored('event', (int) $result->id) ?? [], ['workflow' => true]),
        );
    }

    #[Test]
    public function aNewRecordStoresItsTitlePathWorkflowAndParentAsADraft(): void
    {
        $result = $this->records()->create(new NewRecord(
            'page',
            'Our team',
            '/about/our-team',
            'landing',
            new RecordReference('page', 1),
        ));

        static::assertSame(
            [
                'title'    => 'Our team',
                'path'     => '/about/our-team',
                'status'   => 'draft',
                'workflow' => 'landing',
                'parent'   => 1,
            ],
            $this->host->stored('page', (int) $result->id),
        );
    }

    #[Test]
    public function aNewRecordTakesTheSectionsFirstWorkflowWhenItNamesNone(): void
    {
        $result = $this->records()->create(new NewRecord('page', 'Contact', '/contact'));

        static::assertSame('standard', $this->host->stored('page', (int) $result->id)['workflow'] ?? null);
    }

    #[Test]
    public function aRecordsValuesReadWithDefaultsWhereTheyAreMissingOrWrong(): void
    {
        $this->host->seed('page', 4, ['status' => 'gone']);

        static::assertEquals(
            new RecordSummary(
                new RecordReference('page', 4),
                '',
                RecordStatus::Published,
                null,
                '/admin/records/page/4',
            ),
            $this->records()->describe(new RecordReference('page', 4)),
        );
    }

    #[Test]
    public function aSearchFindsTwentyUnlessItSaysOtherwise(): void
    {
        for ($id = 1; $id <= 21; $id++) {
            $this->host->seed('page', $id, ['title' => "Page {$id}", 'path' => "/page-{$id}"]);
        }

        static::assertCount(20, $this->records()->search('page'));
    }

    /**
     * @param array<string, string> $values
     */
    #[Test]
    #[DataProvider('multibyteProvider')]
    public function aSearchIgnoresTheCaseOfLettersBeyondAscii(array $values, string $text): void
    {
        $this->host->seed('page', 1, $values);

        static::assertCount(1, $this->records()->search($text));
    }

    #[Test]
    public function aSearchWithANegativeLimitFindsNothing(): void
    {
        $this->host->seed('page', 1, ['title' => 'About us', 'path' => '/about']);
        $this->host->seed('page', 2, ['title' => 'About them', 'path' => '/about-them']);

        static::assertSame([], $this->records()->search('about', limit: -1));
    }

    #[Test]
    public function aSearchWithNoRoomFindsNothing(): void
    {
        $this->host->seed('page', 1, ['title' => 'About us', 'path' => '/about']);

        static::assertSame([], $this->records()->search('about', limit: 0));
    }

    #[Test]
    public function outsideARequestTheEditorMayAddAnywhereLinkable(): void
    {
        $result = $this->records()->create(new NewRecord('page', 'Contact', '/contact'));

        static::assertTrue($result->succeeded());
    }

    #[Override]
    protected function setUp(): void
    {
        $http       = new Psr17Factory();
        $this->host = new InMemoryHost(
            $http,
            $http,
            new FrozenClock(new DateTimeImmutable('2026-10-23T09:00:00+00:00')),
        );
        $this->host->linkable(new LinkableSection(
            'page',
            'Page',
            creatable: true,
            workflows: [new Workflow('standard', 'Standard page'), new Workflow('landing', 'Landing page')],
        ));
    }

    /**
     * The records the host lends the probe add-on, booted with nothing else.
     */
    private function records(): RecordsInterface
    {
        $this->host->boot([ProbeExtension::HANDLE => new ProbeExtension(new Journal())], [ProbeExtension::HANDLE]);
        $records = $this->host->context(ProbeExtension::HANDLE)->services()->get(RecordsInterface::class);
        static::assertInstanceOf(RecordsInterface::class, $records);

        return $records;
    }
}
