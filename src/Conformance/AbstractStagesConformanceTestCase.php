<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance;

use Contenir\Extension\Api\Event\Extension\ExtensionPostFail;
use Contenir\Extension\Api\Event\Outcome;
use Override;
use PHPUnit\Framework\Attributes\Test;

use function array_map;

/**
 * The three stages of a save and what each guarantees (plan section 8): Pre
 * before anything is written, the main stage inside the transaction, and
 * Post after it, whatever happened, in the background by default.
 *
 * @api
 */
abstract class AbstractStagesConformanceTestCase extends AbstractConformanceTestCase
{
    #[Test]
    public function aFailingPostListenerLeavesTheSaveDoneAndIsReported(): void
    {
        $this->bindProbe('fail', 'RecordPostSave');
        $outcome = $this->host->save(self::SECTION, 1, ['title' => 'New title']);
        $this->host->runBackground();

        static::assertSame(
            [Outcome::Done, ['probe listener fail']],
            [
                $outcome->outcome,
                array_map(
                    static fn(ExtensionPostFail $failure): string => "{$failure->handle->value} {$failure->point->value} {$failure->name}",
                    $this->host->failures(),
                ),
            ],
        );
    }

    #[Test]
    public function aFailingPreListenerStopsTheSave(): void
    {
        $this->bindProbe('fail', 'RecordPreSave');
        $outcome = $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame(
            [Outcome::Stopped, 'Old title'],
            [$outcome->outcome, $this->host->stored(self::SECTION, 1)['title'] ?? null],
        );
    }

    #[Test]
    public function aFieldErrorHoldsTheSaveButLetsLaterListenersRun(): void
    {
        $this->bindProbe('reject-title', 'RecordPreSave');
        $this->bindProbe('record', 'RecordPreSave');
        $outcome = $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame(
            [Outcome::Stopped, ['title' => ['The probe rejects this title']], true, 'Old title'],
            [
                $outcome->outcome,
                $outcome->errors,
                $this->journal->has('RecordPreSave'),
                $this->host->stored(self::SECTION, 1)['title'] ?? null,
            ],
        );
    }

    #[Test]
    public function aMainStageFailureRollsBackANewRecord(): void
    {
        $this->bindProbe('fail', 'RecordSave');
        $outcome = $this->host->save(self::SECTION, null, ['title' => 'Brand new']);

        static::assertSame([Outcome::Failed, null], [$outcome->outcome, $outcome->id]);
    }

    #[Test]
    public function aMainStageFailureRollsTheSaveBack(): void
    {
        $this->bindProbe('fail', 'RecordSave');
        $outcome = $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame(
            [Outcome::Failed, ['title' => 'Old title', 'slug' => 'old-title']],
            [$outcome->outcome, $this->host->stored(self::SECTION, 1)],
        );
    }

    #[Test]
    public function aNewRecordIsGivenAnId(): void
    {
        $outcome = $this->host->save(self::SECTION, null, ['title' => 'Brand new']);

        static::assertSame(
            [Outcome::Done, ['title' => 'Brand new']],
            [$outcome->outcome, null === $outcome->id ? null : $this->host->stored(self::SECTION, $outcome->id)],
        );
    }

    #[Test]
    public function aPostListenerHearsThatASaveFailed(): void
    {
        $this->bindProbe('fail', 'RecordSave');
        $this->bindProbe('record-now', 'RecordPostSave');
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame(['RecordPostSave:failed'], $this->journal->entries());
    }

    #[Test]
    public function aPostListenerRunsWhateverHappened(): void
    {
        $this->bindProbe('stop', 'RecordPreSave');
        $this->bindProbe('record-now', 'RecordPostSave');
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame(['RecordPostSave:stopped'], $this->journal->entries());
    }

    #[Test]
    public function aPostListenerWaitsForTheJobRunnerByDefault(): void
    {
        $this->bindProbe('record', 'RecordPostSave');
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);
        $before = $this->journal->entries();
        $this->host->runBackground();

        static::assertSame([[], ['RecordPostSave:done']], [$before, $this->journal->entries()]);
    }

    #[Test]
    public function aPreListenerCanChangeAValueBeforeItIsWritten(): void
    {
        $this->bindProbe('change-value', 'RecordPreSave');
        $this->host->save(self::SECTION, 1, ['title' => 'New Title']);

        static::assertSame(
            ['title' => 'New Title', 'slug' => 'new-title'],
            $this->host->stored(self::SECTION, 1),
        );
    }

    #[Test]
    public function aPreListenerCanStopASave(): void
    {
        $this->bindProbe('stop', 'RecordPreSave');
        $outcome = $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame(
            [Outcome::Stopped, 'Stopped by the probe', ['title' => 'Old title', 'slug' => 'old-title']],
            [$outcome->outcome, $outcome->message, $this->host->stored(self::SECTION, 1)],
        );
    }

    #[Test]
    public function aSaveKeepsTheFieldsItWasNotGiven(): void
    {
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame(['title' => 'New title', 'slug' => 'old-title'], $this->host->stored(self::SECTION, 1));
    }

    #[Test]
    public function everyBoundPostListenerRuns(): void
    {
        $this->bindProbe('record-now', 'RecordPostSave');
        $this->bindProbe('record', 'RecordPostSave');
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);
        $this->host->runBackground();

        static::assertSame(['RecordPostSave:done', 'RecordPostSave:done'], $this->journal->entries());
    }

    #[Test]
    public function stoppingASaveEndsTheChain(): void
    {
        $this->bindProbe('stop', 'RecordPreSave');
        $this->bindProbe('record', 'RecordPreSave');
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertFalse($this->journal->has('RecordPreSave'));
    }

    #[Test]
    public function theStagesRunInOrder(): void
    {
        $this->bindProbe('record', 'RecordPreSave');
        $this->bindProbe('record', 'RecordSave');
        $this->bindProbe('record-now', 'RecordPostSave');
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);
        $this->host->runBackground();

        static::assertSame(['RecordPreSave', 'RecordSave', 'RecordPostSave:done'], $this->journal->entries());
    }

    #[Test]
    public function warningsAndFlagsLetTheSaveGoAhead(): void
    {
        $this->bindProbe('warn', 'RecordPreSave');
        $outcome = $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame(
            [Outcome::Done, ['title' => ['The probe noticed the title']], ['probed']],
            [$outcome->outcome, $outcome->warnings, $outcome->flags],
        );
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->bootProbe();
        $this->host->seed(self::SECTION, 1, ['title' => 'Old title', 'slug' => 'old-title']);
    }
}
