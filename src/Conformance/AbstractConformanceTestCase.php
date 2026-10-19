<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance;

use Contenir\Extension\Api\Conformance\Probe\Journal;
use Contenir\Extension\Api\Conformance\Probe\ProbeExtension;
use Override;
use PHPUnit\Framework\TestCase;

/**
 * What every conformance test case shares: a fresh host and a fresh journal
 * for each test, and the probe add-on loaded into the host.
 *
 * A host proves it implements the API by extending each conformance test
 * case and returning itself, ready to boot, from createHost().
 *
 * @api
 */
abstract class AbstractConformanceTestCase extends TestCase
{
    /**
     * The section the probe's listeners are bound to unless a test says
     * otherwise.
     */
    protected const string SECTION = 'article';

    protected HostDriverInterface $host;

    protected Journal $journal;

    /**
     * A new host with nothing loaded.
     */
    abstract protected function createHost(): HostDriverInterface;

    /**
     * Binds one of the probe's listeners, by default to the article section.
     *
     * @param list<string> $fields
     */
    protected function bindProbe(
        string $listener,
        string $event,
        ?string $section = self::SECTION,
        array $fields = [],
        ?int $order = null,
    ): void {
        $this->host->bind($section, ProbeExtension::HANDLE, $listener, $event, $fields, $order);
    }

    /**
     * Installs, enables and loads the probe add-on.
     */
    protected function bootProbe(): void
    {
        $this->host->boot(
            [ProbeExtension::HANDLE => new ProbeExtension($this->journal)],
            [ProbeExtension::HANDLE],
            [
                Journal::class => $this->journal,
            ],
        );
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->journal = new Journal();
        $this->host    = $this->createHost();
    }
}
