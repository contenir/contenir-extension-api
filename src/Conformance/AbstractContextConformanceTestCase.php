<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance;

use Contenir\Extension\Api\Conformance\Probe\Event\Announced;
use Contenir\Extension\Api\Conformance\Probe\ProbeExtension;
use Override;
use PHPUnit\Framework\Attributes\Test;

/**
 * What an add-on's code gets from its context (plan sections 8 and 9, P15):
 * its own handle, a logger stamped with it, and a dispatcher for its own
 * events that refuses core events and recurring chains.
 *
 * @api
 */
abstract class AbstractContextConformanceTestCase extends AbstractConformanceTestCase
{
    #[Test]
    public function aFailureIsReportedWithTheAddOnAndWhere(): void
    {
        $this->bindProbe('fail', 'RecordPreSave');
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        $failure = $this->host->failures()[0] ?? null;

        static::assertSame(
            [ProbeExtension::HANDLE, 'fail', false],
            [$failure?->handle->value, $failure?->name, $failure?->succeeded()],
        );
    }

    #[Test]
    public function anAddOnCanFireItsOwnEvents(): void
    {
        $this->bindProbe('announce', 'RecordPreSave');
        $this->bindProbe('repeat', Announced::class, null);
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame('echo', $this->journal->entries()[0] ?? null);
    }

    #[Test]
    public function anAddOnCannotFireACoreEvent(): void
    {
        $this->bindProbe('forge', 'RecordPreSave');
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame(['refused:core'], $this->journal->entries());
    }

    #[Test]
    public function anAddOnsLogEntriesCarryItsSource(): void
    {
        $this->bindProbe('log', 'RecordPreSave');
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        $sources = [];
        foreach ($this->host->logs() as $entry) {
            if ('The probe was here' !== $entry['message']) {
                continue;
            }

            $sources[] = $entry['context']['source'] ?? null;
        }

        static::assertSame(['extension.probe'], $sources);
    }

    #[Test]
    public function anAddOnsOwnEventsReachOnlyBoundListeners(): void
    {
        $this->bindProbe('announce', 'RecordPreSave');
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame(['announced'], $this->journal->entries());
    }

    #[Test]
    public function anEventCannotRecurWithinItsChain(): void
    {
        $this->bindProbe('announce', 'RecordPreSave');
        $this->bindProbe('repeat', Announced::class, null);
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame(['echo', 'refused:recurrence', 'announced'], $this->journal->entries());
    }

    #[Test]
    public function theContextNamesTheAddOn(): void
    {
        $this->bindProbe('name', 'RecordPreSave');
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame(['handle:probe'], $this->journal->entries());
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->bootProbe();
        $this->host->seed(self::SECTION, 1, ['title' => 'Old title']);
    }
}
