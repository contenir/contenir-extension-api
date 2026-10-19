<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Event\ActionResult;
use Contenir\Extension\Api\Event\Change;
use Contenir\Extension\Api\Event\Outcome;
use Contenir\Extension\Api\Event\Record\AbstractRecordPreEvent;
use Contenir\Extension\Api\Event\Record\RecordCreate;
use Contenir\Extension\Api\Event\Record\RecordPostCreate;
use Contenir\Extension\Api\Event\Record\RecordPostSave;
use Contenir\Extension\Api\Event\Record\RecordPreCreate;
use Contenir\Extension\Api\Event\Record\RecordPreSave;
use Contenir\Extension\Api\Event\Record\RecordSave;

/**
 * Saves a record in three stages: Pre listeners before anything is written,
 * main-stage listeners inside the transaction, and Post listeners after it,
 * whatever happened. A new record fires the Create events and then the Save
 * events at each stage, so a listener to Save hears creations too.
 *
 * @internal
 */
final readonly class Saver
{
    public const string HELD = 'Some fields need attention';

    public function __construct(
        private Records $records,
        private Dispatcher $dispatcher,
    ) {}

    /**
     * @param array<string, mixed> $values
     */
    public function save(string $section, string|int|null $id, array $values): ActionResult
    {
        $before = null === $id ? [] : $this->records->get($section, $id) ?? [];
        $change = new Change($before, [...$before, ...$values]);
        $notes  = new PreNotes();

        foreach ($this->preEvents($section, $id) as $make) {
            $pre = $make($change);
            $this->dispatcher->dispatch($section, $pre);
            $change = $pre->change();
            $notes->add($pre);
            if (! $pre->proceeds()) {
                $this->post($section, $id, $id, $change, Outcome::Stopped, $pre->stopMessage() ?? self::HELD);

                return $notes->result(Outcome::Stopped, $id, $pre->stopMessage() ?? self::HELD, $pre->stopDetail());
            }
        }

        return $this->commit($section, $id, $change, $notes);
    }

    /**
     * Writes the record and runs the main stage inside a transaction, rolling
     * both back when a listener fails.
     */
    private function commit(string $section, string|int|null $id, Change $change, PreNotes $notes): ActionResult
    {
        $snapshot = $this->records->snapshot();
        $saved    = $this->records->write($section, $id, $change->after);
        $failure  = null === $id
            ? $this->dispatcher->dispatch($section, new RecordCreate($section, $saved, $change))
            : null;
        $failure ??= $this->dispatcher->dispatch($section, new RecordSave($section, $saved, $change));
        if (null !== $failure) {
            $this->records->restore($snapshot);
            $this->post($section, $id, $id, $change, Outcome::Failed, $failure);

            return new ActionResult(Outcome::Failed, $id, $failure);
        }

        $this->post($section, $id, $saved, $change, Outcome::Done, '');

        return $notes->result(Outcome::Done, $saved, '', '');
    }

    /**
     * Fires the Post events: RecordPostCreate first when the record was new,
     * then RecordPostSave.
     *
     * @param string|int|null $was The id the save started with, null for a new record
     * @param string|int|null $is  The record's id now, null when a new one was not stored
     *
     * @mago-expect lint:excessive-parameter-list One parameter per part of a Post event.
     */
    private function post(
        string $section,
        string|int|null $was,
        string|int|null $is,
        Change $change,
        Outcome $outcome,
        string $reason,
    ): void {
        if (null === $was) {
            $this->dispatcher->dispatch($section, new RecordPostCreate($section, $is, $change, $outcome, $reason));
        }

        $this->dispatcher->dispatch($section, new RecordPostSave($section, $is, $change, $outcome, $reason));
    }

    /**
     * Makers of the Pre events a save fires, in order.
     *
     * @return list<callable(Change): AbstractRecordPreEvent>
     */
    private function preEvents(string $section, string|int|null $id): array
    {
        $save = static fn(Change $change): AbstractRecordPreEvent => new RecordPreSave($section, $id, $change);
        if (null !== $id) {
            return [$save];
        }

        return [
            static fn(Change $change): AbstractRecordPreEvent => new RecordPreCreate($section, null, $change),
            $save,
        ];
    }
}
