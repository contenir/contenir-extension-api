<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance;

use PHPUnit\Framework\Attributes\Test;

use function array_column;
use function count;
use function implode;
use function is_string;
use function range;

/**
 * The record updates a host lends add-ons (Record\RecordUpdatesInterface), on top of the records they
 * are made to (a host that lends updates extends this case in place of the records one): each update is
 * saved through the normal save path, so its listeners can stop or fail it and the editor needs
 * permission to update its section; each record succeeds or fails on its own, a stopped one leaves the
 * others saved, and a Done update answers what its changed field held before.
 *
 * @api
 */
abstract class AbstractRecordUpdatesConformanceTestCase extends AbstractRecordsConformanceTestCase
{
    #[Test]
    public function aCallTakesFiftyUpdatesAndRefusesMore(): void
    {
        $fifty    = $this->updates($this->ids(50), 'title', 'x');
        $fiftyOne = $this->refusal($this->ids(51));

        static::assertSame([50, 'One call takes at most 50 record updates'], [count($fifty), $fiftyOne]);
    }

    #[Test]
    public function anEditorNeedsPermissionToUpdateTheSection(): void
    {
        $denied  = $this->updates([2], 'title', 'Meet the team', [self::HANDLER, self::PAGES . ':add']);
        $allowed = $this->updates([2], 'title', 'Meet the team', [self::HANDLER, self::PAGES . ':update']);

        static::assertSame(
            [['stopped'], ['done']],
            [array_column($denied, 'outcome'), array_column($allowed, 'outcome')],
        );
    }

    #[Test]
    public function anUpdateAPreListenerStopsSavesNothing(): void
    {
        $this->bindProbe('stop', 'RecordPreSave', self::PAGES);

        $answers = $this->updates([2], 'title', 'Meet the team');

        static::assertSame(
            [['stopped'], [[]], 'Our team'],
            [array_column($answers, 'outcome'), array_column($answers, 'previous'), $this->titled(2)],
        );
    }

    #[Test]
    public function anUpdateFiresTheSaveEventsAndNotTheCreateOnes(): void
    {
        $this->bindProbe('record', 'RecordPreCreate', self::PAGES);
        $this->bindProbe('record', 'RecordPreSave', self::PAGES);
        $this->bindProbe('record', 'RecordSave', self::PAGES);

        $this->updates([2], 'title', 'Meet the team');

        static::assertSame(['RecordPreSave', 'RecordSave'], $this->journal->entries());
    }

    #[Test]
    public function anUpdateKeepsTheFieldsItLeavesOut(): void
    {
        $this->updates([2], 'title', 'Meet the team');

        static::assertSame(
            ['/about/our-team', 'draft'],
            [$this->described(2)['path'] ?? null, $this->described(2)['status'] ?? null],
        );
    }

    #[Test]
    public function anUpdateSavesTheFieldAndAnswersWhatItHeldBefore(): void
    {
        $answers = $this->updates([2], 'title', 'Meet the team');

        static::assertSame(
            [[self::PAGES . ':2'], ['done'], [['title' => 'Our team']], 'Meet the team'],
            [
                array_column($answers, 'record'),
                array_column($answers, 'outcome'),
                array_column($answers, 'previous'),
                $this->titled(2),
            ],
        );
    }

    #[Test]
    public function anUpdateThatFailsInTheMainStageRollsBack(): void
    {
        $this->bindProbe('fail', 'RecordSave', self::PAGES);

        $answers = $this->updates([2], 'title', 'Meet the team');

        static::assertSame(
            [['failed'], [[]], 'Our team'],
            [array_column($answers, 'outcome'), array_column($answers, 'previous'), $this->titled(2)],
        );
    }

    #[Test]
    public function aRecordThatDoesNotExistIsStoppedAndTheOthersAreStillSaved(): void
    {
        $answers = $this->updates([99, 2], 'title', 'Meet the team');

        static::assertSame(
            [['stopped', 'done'], 'Meet the team'],
            [array_column($answers, 'outcome'), $this->titled(2)],
        );
    }

    #[Test]
    public function aSectionThatIsNotLinkableIsNotUpdated(): void
    {
        $answers = $this->updates([3], 'title', 'Taken over', section: self::HIDDEN);

        static::assertSame(['stopped'], array_column($answers, 'outcome'));
    }

    /**
     * A page as the probe describes it, empty when the host knows none.
     *
     * @return array<string, mixed>
     *
     * @mago-expect analysis:mixed-assignment The probe's answer is decoded JSON, checked to be an array next.
     */
    private function described(int $id): array
    {
        $summary = $this->ask(['do' => 'describe', 'section' => self::PAGES, 'id' => (string) $id]);
        static::assertIsArray($summary);

        /** @var array<string, mixed> $summary */
        return $summary;
    }

    /**
     * The ids 1 to $last, which the seeded records and a few that do not exist stand under.
     *
     * @return list<int>
     */
    private function ids(int $last): array
    {
        return range(
            start: 1,
            end: $last,
        );
    }

    /**
     * Why the host refused the update of these records, from the probe's answer.
     *
     * @param list<int> $ids
     *
     * @mago-expect analysis:mixed-assignment The probe's answer is decoded JSON, checked to be an array next.
     */
    private function refusal(array $ids): ?string
    {
        $answer = $this->ask(
            [
                'do'      => 'update',
                'section' => self::PAGES,
                'ids'     => implode(',', $ids),
                'field'   => 'title',
                'value'   => 'x',
            ],
            method: 'POST',
        );
        static::assertIsArray($answer);

        return is_string($answer['refused'] ?? null) ? $answer['refused'] : null;
    }

    /**
     * What a page is titled now.
     */
    private function titled(int $id): mixed
    {
        return $this->described($id)['title'] ?? null;
    }

    /**
     * What the probe's call to the host's record updates answered, one entry for each record.
     *
     * @param list<int>    $ids
     * @param list<string> $grants
     *
     * @return list<array{record: string, outcome: string, previous: array<string, mixed>, errors: list<string>}>
     */
    private function updates(
        array $ids,
        string $field,
        string $value,
        array $grants = [self::HANDLER, self::PAGES],
        string $section = self::PAGES,
    ): array {
        /** @var list<array{record: string, outcome: string, previous: array<string, mixed>, errors: list<string>}> */
        return $this->ask(
            [
                'do'      => 'update',
                'section' => $section,
                'ids'     => implode(',', $ids),
                'field'   => $field,
                'value'   => $value,
            ],
            $grants,
            'POST',
        );
    }
}
