<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance;

use Contenir\Extension\Api\Conformance\Probe\ProbeExtension;
use Contenir\Extension\Api\Event\Extension\ExtensionPostFail;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

use function array_map;
use function http_build_query;

/**
 * An add-on's own settings, through its context: it reads back what it
 * saved, a field it never saved has its declared default, writing fires the
 * settings events, and it can't write a field it didn't declare. The
 * operations on its settings section run with its context, behind their
 * privilege on the section's resource.
 *
 * @api
 */
abstract class AbstractSettingsConformanceTestCase extends AbstractConformanceTestCase
{
    private const array GRANTS = ['extension.probe.remember'];

    private const string SECTION_RESOURCE = 'settings.system.probe';

    #[Test]
    public function aFieldNeverSavedHasItsDeclaredDefault(): void
    {
        $response = $this->remember(['field' => 'colour', 'note' => 'red']);

        static::assertSame(
            '{"outcome":"done","note":"none yet","all":{"note":"none yet","colour":"red"}}',
            (string) $response->getBody(),
        );
    }

    #[Test]
    public function anAddOnCannotWriteAFieldItDidNotDeclare(): void
    {
        static::assertSame(422, $this->remember(['field' => 'nope', 'note' => 'x'])->getStatusCode());
    }

    #[Test]
    public function anAddOnReadsBackWhatItSaved(): void
    {
        $response = $this->remember(['note' => 'hello']);

        static::assertSame(
            [200, '{"outcome":"done","note":"hello","all":{"note":"hello","colour":null}}'],
            [$response->getStatusCode(), (string) $response->getBody()],
        );
    }

    #[Test]
    public function anOperationLogsUnderItsAddOn(): void
    {
        $this->host->operate(ProbeExtension::HANDLE, 'probe', 'note', [], [self::SECTION_RESOURCE]);

        static::assertContains(
            ['level' => 'info', 'message' => 'The note operation ran', 'context' => ['source' => 'extension.probe']],
            $this->host->logs(),
        );
    }

    #[Test]
    public function anOperationNeedsItsPrivilegeOnTheSection(): void
    {
        static::assertSame(
            [null, 'success'],
            [
                $this->host->operate(ProbeExtension::HANDLE, 'probe', 'note', [], [self::SECTION_RESOURCE . ':view']),
                $this->host
                    ->operate(ProbeExtension::HANDLE, 'probe', 'note', [], [self::SECTION_RESOURCE . ':update'])
                    ?->messages()[0]['level'] ?? null,
            ],
        );
    }

    #[Test]
    public function anOperationRunsWithItsAddOnsContext(): void
    {
        $this->remember(['note' => 'hello']);

        static::assertSame(
            [['level' => 'success', 'text' => 'probe: hello, posted blue, on settings.system.probe']],
            $this->host
                ->operate(ProbeExtension::HANDLE, 'probe', 'note', ['value' => 'blue'], [self::SECTION_RESOURCE])
                ?->messages(),
        );
    }

    #[Test]
    public function anOperationThatFailsIsReportedUnderItsAddOn(): void
    {
        $result = $this->host->operate(ProbeExtension::HANDLE, 'probe', 'broken', [], [self::SECTION_RESOURCE]);

        static::assertSame(
            [
                [['level' => 'danger', 'text' => 'That could not be done. The log has the details.']],
                [['probe', 'operation', 'broken']],
            ],
            [
                $result?->messages(),
                array_map(
                    /** @return array{string, string, string} */
                    static fn(ExtensionPostFail $failure): array => [
                        $failure->handle->value,
                        $failure->point->value,
                        $failure->name,
                    ],
                    $this->host->failures(),
                ),
            ],
        );
    }

    #[Test]
    public function aStoppedWriteLeavesTheSettingsAlone(): void
    {
        $this->bindProbe('stop-settings', 'SettingsPreSave', ProbeExtension::HANDLE);
        $response = $this->remember(['note' => 'hello']);

        static::assertSame(
            '{"outcome":"stopped","note":"none yet","all":{"note":"none yet","colour":null}}',
            (string) $response->getBody(),
        );
    }

    #[Test]
    public function aStoppedWriteStillTellsThePostListeners(): void
    {
        $this->bindProbe('stop-settings', 'SettingsPreSave', ProbeExtension::HANDLE);
        $this->bindProbe('record-now', 'SettingsPostSave', ProbeExtension::HANDLE);
        $this->remember(['note' => 'hello']);

        static::assertSame(['SettingsPostSave:stopped'], $this->journal->entries());
    }

    #[Test]
    public function aWriteKeepsTheFieldsItWasNotGiven(): void
    {
        $this->remember(['note' => 'hello']);
        $response = $this->remember(['field' => 'colour', 'note' => 'red']);

        static::assertSame(
            '{"outcome":"done","note":"hello","all":{"note":"hello","colour":"red"}}',
            (string) $response->getBody(),
        );
    }

    #[Test]
    public function writingSettingsFiresTheSettingsEvents(): void
    {
        $this->bindProbe('record-now', 'SettingsPostSave', ProbeExtension::HANDLE);
        $this->remember(['note' => 'hello']);

        static::assertSame(['SettingsPostSave:done'], $this->journal->entries());
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
    }

    /**
     * @param array<string, string> $query
     */
    private function remember(array $query): ResponseInterface
    {
        $request = $this->requests()->createServerRequest(
            'GET',
            'https://cms.example.com/admin/extension/probe/remember?' . http_build_query($query),
        );

        return $this->host->handle($request->withQueryParams($query), self::GRANTS);
    }
}
