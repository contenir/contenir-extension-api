<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Integration\Testing;

use Contenir\Extension\Api\Conformance\Probe\Journal;
use Contenir\Extension\Api\Conformance\Probe\ProbeExtension;
use Contenir\Extension\Api\Event\Outcome;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Record\LinkableSection;
use Contenir\Extension\Api\Record\RecordUpdate;
use Contenir\Extension\Api\Record\RecordUpdatesInterface;
use Contenir\Extension\Api\Testing\FrozenClock;
use Contenir\Extension\Api\Testing\InMemoryHost;
use Contenir\Extension\Api\Url\RecordReference;
use DateTimeImmutable;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;
use function range;

/**
 * The in-memory host's record updates: what each save answers, what the changed fields held before, and
 * that one record's trouble leaves the others saved.
 */
#[Group('integration')]
final class InMemoryRecordUpdatesTest extends TestCase
{
    private InMemoryHost $host;

    #[Test]
    public function answersWhatTheChangedFieldsHeldBeforeAndNothingElse(): void
    {
        $this->host->seed('page', 1, ['title' => 'About', 'seo' => '{"title":"About us"}']);

        $result = $this->updates()->update([
            new RecordUpdate(new RecordReference('page', 1), ['seo' => '{"title":"About"}', 'summary' => 'Hello']),
        ])[0];

        static::assertSame(['seo' => '{"title":"About us"}', 'summary' => null], $result->previous);
    }

    #[Test]
    public function aRecordThatDoesNotExistIsStoppedAndTheOthersAreStillSaved(): void
    {
        $this->host->seed('page', 1, ['title' => 'About', 'seo' => '']);

        $results = $this->updates()->update([
            new RecordUpdate(new RecordReference('page', 99), ['seo' => 'x']),
            new RecordUpdate(new RecordReference('page', 1), ['seo' => 'y']),
        ]);

        static::assertSame(
            [Outcome::Stopped, [], Outcome::Done],
            [$results[0]->result->outcome, $results[0]->previous, $results[1]->result->outcome],
        );
    }

    #[Test]
    public function refusesMoreThanFiftyUpdatesInOneCall(): void
    {
        $updates = array_map(
            static fn(int $id): RecordUpdate => new RecordUpdate(new RecordReference('page', $id), ['seo' => 'x']),
            range(1, RecordUpdatesInterface::MAX_RECORDS + 1),
        );

        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('One call takes at most 50 record updates');

        $this->updates()->update($updates);
    }

    #[Test]
    public function savesEachUpdateAndAnswersInTheOrderGiven(): void
    {
        $this->host->seed('page', 1, ['title' => 'About', 'seo' => '']);
        $this->host->seed('page', 2, ['title' => 'Contact', 'seo' => '']);

        $results = $this->updates()->update([
            new RecordUpdate(new RecordReference('page', 2), ['seo' => '{"description":"Call us"}']),
            new RecordUpdate(new RecordReference('page', 1), ['seo' => '{"description":"About us"}']),
        ]);

        static::assertSame(
            [[2, 1], [true, true]],
            [
                array_map(static fn($result) => $result->record->id, $results),
                array_map(static fn($result) => $result->succeeded(), $results),
            ],
        );
    }

    #[Test]
    public function takesFiftyUpdatesAtOnce(): void
    {
        $updates = array_map(
            static fn(int $id): RecordUpdate => new RecordUpdate(new RecordReference('page', $id), ['seo' => 'x']),
            range(1, RecordUpdatesInterface::MAX_RECORDS),
        );

        static::assertCount(RecordUpdatesInterface::MAX_RECORDS, $this->updates()->update($updates));
    }

    #[Override]
    protected function setUp(): void
    {
        $http       = new Psr17Factory();
        $this->host = new InMemoryHost(
            $http,
            $http,
            new FrozenClock(new DateTimeImmutable('2026-11-06T09:00:00+00:00')),
        );
        $this->host->linkable(new LinkableSection('page', 'Page', creatable: true));
    }

    /**
     * The record updates the host lends the probe add-on, booted with nothing else.
     */
    private function updates(): RecordUpdatesInterface
    {
        $this->host->boot([ProbeExtension::HANDLE => new ProbeExtension(new Journal())], [ProbeExtension::HANDLE]);
        $updates = $this->host->context(ProbeExtension::HANDLE)->services()->get(RecordUpdatesInterface::class);
        static::assertInstanceOf(RecordUpdatesInterface::class, $updates);

        return $updates;
    }
}
