<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance;

use Contenir\Extension\Api\Record\LinkableSection;
use Contenir\Extension\Api\Record\RecordStatus;
use Contenir\Extension\Api\Record\Workflow;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestFactoryInterface;

use function array_column;
use function http_build_query;
use function json_decode;

/**
 * The records a host lends add-ons (Record\RecordsInterface): only the
 * sections the site marks as linkable are listed, searched, described and
 * created in; a new record is a draft saved through the normal save path,
 * so its listeners can stop or fail it, its fields can hold it, and the
 * editor needs permission to add to its section.
 *
 * @api
 */
abstract class AbstractRecordsConformanceTestCase extends AbstractConformanceTestCase
{
    /**
     * A creatable section, with two workflows.
     */
    protected const string PAGES = 'page';

    /**
     * A linkable section that is not creatable.
     */
    protected const string NEWS = 'news';

    /**
     * A section that is not linkable.
     */
    protected const string HIDDEN = 'secret';

    /**
     * What lets a request reach the probe's records handler.
     */
    private const string HANDLER = 'extension.probe.records';

    #[Test]
    public function aCreateAFieldHoldsIsStoppedWithItsErrors(): void
    {
        $answers = [
            $this->create(['title' => 'About', 'path' => '/about']),
            $this->create(['title' => 'Contact', 'path' => '/contact', 'workflow' => 'gallery']),
        ];

        static::assertSame(
            [['stopped', ['path']], ['stopped', ['workflow']]],
            [[$answers[0]['outcome'], $answers[0]['errors']], [$answers[1]['outcome'], $answers[1]['errors']]],
        );
    }

    #[Test]
    public function aCreateAPreListenerStopsStoresNothing(): void
    {
        $this->bindProbe('stop', 'RecordPreSave', self::PAGES);

        static::assertSame(
            ['stopped', true],
            [
                $this->create(['title' => 'Contact', 'path' => '/contact'])['outcome'],
                $this->ask(['do' => 'free', 'path' => '/contact']),
            ],
        );
    }

    #[Test]
    public function aCreatedRecordIsADraftThatSearchAndDescribeFind(): void
    {
        $created = $this->create(['title' => 'Contact', 'path' => '/contact', 'workflow' => 'landing']);

        static::assertSame(
            [
                'done',
                [
                    'record' => self::PAGES . ":{$created['id']}",
                    'title'  => 'Contact',
                    'status' => 'draft',
                    'path'   => '/contact',
                    'edit'   => true,
                ],
                false,
            ],
            [
                $created['outcome'],
                $this->ask(['do' => 'describe', 'section' => self::PAGES, 'id' => (string) $created['id']]),
                $this->ask(['do' => 'free', 'path' => '/contact']),
            ],
        );
    }

    #[Test]
    public function aCreateFiresTheCreateAndSaveEvents(): void
    {
        $this->bindProbe('record', 'RecordPreCreate', self::PAGES);
        $this->bindProbe('record', 'RecordCreate', self::PAGES);
        $this->bindProbe('record', 'RecordPreSave', self::PAGES);
        $this->bindProbe('record', 'RecordSave', self::PAGES);

        $this->create(['title' => 'Contact', 'path' => '/contact']);

        static::assertSame(
            ['RecordPreCreate', 'RecordPreSave', 'RecordCreate', 'RecordSave'],
            $this->journal->entries(),
        );
    }

    #[Test]
    public function aCreateThatFailsInTheMainStageRollsBack(): void
    {
        $this->bindProbe('fail', 'RecordCreate', self::PAGES);

        static::assertSame(
            ['failed', true],
            [
                $this->create(['title' => 'Contact', 'path' => '/contact'])['outcome'],
                $this->ask(['do' => 'free', 'path' => '/contact']),
            ],
        );
    }

    #[Test]
    public function anEditorNeedsPermissionToAddToTheSection(): void
    {
        $answers = [
            $this->create(['title' => 'Contact', 'path' => '/contact'], [self::HANDLER, self::PAGES . ':update']),
            $this->create(['title' => 'Contact', 'path' => '/contact'], [self::HANDLER, self::PAGES . ':add']),
        ];

        static::assertSame(['stopped', 'done'], [$answers[0]['outcome'], $answers[1]['outcome']]);
    }

    #[Test]
    public function aPathInUseIsNotFree(): void
    {
        static::assertSame(
            [false, true],
            [$this->ask(['do' => 'free', 'path' => '/about']), $this->ask(['do' => 'free', 'path' => '/contact'])],
        );
    }

    #[Test]
    public function aSectionThatIsNotCreatableRefusesToCreate(): void
    {
        $answer = $this->create(['section' => self::NEWS, 'title' => 'Launch', 'path' => '/news/launch']);

        static::assertSame(['stopped', true], [
            $answer['outcome'],
            $this->ask(['do' => 'free', 'path' => '/news/launch']),
        ]);
    }

    #[Test]
    public function describeGivesARecordsTitleStatusAndPath(): void
    {
        static::assertSame(
            [
                'record' => self::PAGES . ':2',
                'title'  => 'Our team',
                'status' => 'draft',
                'path'   => '/about/our-team',
                'edit'   => true,
            ],
            $this->ask(['do' => 'describe', 'section' => self::PAGES, 'id' => '2']),
        );
    }

    #[Test]
    public function describeKnowsNoRecordOutsideTheLinkableSections(): void
    {
        static::assertSame(
            [null, null],
            [
                $this->ask(['do' => 'describe', 'section' => self::HIDDEN, 'id' => '3']),
                $this->ask(['do' => 'describe', 'section' => self::PAGES, 'id' => '99']),
            ],
        );
    }

    #[Test]
    public function searchFindsTitlesAndPathsInEveryLinkableSectionIgnoringCase(): void
    {
        static::assertSame(
            [self::PAGES . ':2', self::NEWS . ':7'],
            $this->found(['do' => 'search', 'text' => 'TEAM']),
        );
    }

    #[Test]
    public function searchKeepsToTheSectionsNamed(): void
    {
        static::assertSame(
            [self::NEWS . ':7'],
            $this->found(['do' => 'search', 'text' => 'team', 'sections' => self::NEWS]),
        );
    }

    #[Test]
    public function searchMatchesAPath(): void
    {
        static::assertSame([self::PAGES . ':2'], $this->found(['do' => 'search', 'text' => 'our-team']));
    }

    #[Test]
    public function searchReturnsNoMoreThanTheLimitInTitleOrder(): void
    {
        static::assertSame(
            [self::PAGES . ':1', self::PAGES . ':2'],
            $this->found(['do' => 'search', 'text' => '', 'limit' => '2']),
        );
    }

    #[Test]
    public function theSectionsAreTheLinkableOnesInTheSitesOrder(): void
    {
        static::assertSame([self::PAGES, self::NEWS], $this->ask(['do' => 'sections']));
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
        $this->host->linkable(new LinkableSection(
            self::PAGES,
            'Page',
            creatable: true,
            workflows: [new Workflow('standard', 'Standard page'), new Workflow('landing', 'Landing page')],
        ));
        $this->host->linkable(new LinkableSection(self::NEWS, 'News article'));
        $this->host->seed(self::PAGES, 2, [
            'title'  => 'Our team',
            'path'   => '/about/our-team',
            'status' => RecordStatus::Draft->value,
        ]);
        $this->host->seed(self::PAGES, 1, [
            'title'  => 'About us',
            'path'   => '/about',
            'status' => RecordStatus::Published->value,
        ]);
        $this->host->seed(self::NEWS, 7, [
            'title'  => 'Team news',
            'path'   => '/news/team-news',
            'status' => RecordStatus::Published->value,
        ]);
        $this->host->seed(self::HIDDEN, 3, [
            'title'  => 'Team secret',
            'path'   => '/secret',
            'status' => RecordStatus::Published->value,
        ]);
    }

    /**
     * What the probe's records handler answered, decoded.
     *
     * @param array<string, string> $query
     * @param list<string>          $grants
     */
    private function ask(array $query, array $grants = [self::HANDLER, self::PAGES], string $method = 'GET'): mixed
    {
        $response = $this->host->handle(
            $this->requests()
                ->createServerRequest(
                    $method,
                    'https://cms.example.com/admin/extension/probe/records?' . http_build_query($query),
                )
                ->withQueryParams($query),
            $grants,
        );

        return json_decode((string) $response->getBody(), associative: true);
    }

    /**
     * Creates a page through the probe, unless $values name another section.
     *
     * @param array<string, string> $values
     * @param list<string>          $grants
     *
     * @return array{outcome: string, id: string|int|null, errors: list<string>}
     */
    private function create(array $values, array $grants = [self::HANDLER, self::PAGES]): array
    {
        /** @var array{outcome: string, id: string|int|null, errors: list<string>} */
        return $this->ask(['do' => 'create', 'section' => self::PAGES, ...$values], $grants, 'POST');
    }

    /**
     * The records a search found, as "<section>:<id>".
     *
     * @param array<string, string> $query
     *
     * @return list<string>
     */
    private function found(array $query): array
    {
        /** @var list<array{record: string}> $summaries */
        $summaries = $this->ask($query);

        return array_column($summaries, 'record');
    }
}
