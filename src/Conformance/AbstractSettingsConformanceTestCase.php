<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance;

use Contenir\Extension\Api\Conformance\Probe\ProbeExtension;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

use function http_build_query;

/**
 * An add-on's own settings, through its context: it reads back what it
 * saved, a field it never saved has its declared default, writing fires the
 * settings events, and it can't write a field it didn't declare.
 *
 * @api
 */
abstract class AbstractSettingsConformanceTestCase extends AbstractConformanceTestCase
{
    private const array GRANTS = ['extension.probe.remember'];

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
