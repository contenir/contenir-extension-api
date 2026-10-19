<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Conformance\SaveOutcome;
use Contenir\Extension\Api\Event\Change;
use Contenir\Extension\Api\Event\Outcome;
use Contenir\Extension\Api\Event\Record\RecordPostSave;
use Contenir\Extension\Api\Event\Record\RecordPreSave;
use Contenir\Extension\Api\Event\Record\RecordSave;

/**
 * Saves a record in three stages: Pre listeners before anything is written,
 * main-stage listeners inside the transaction, and Post listeners after it,
 * whatever happened.
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
    public function save(string $section, string|int|null $id, array $values): SaveOutcome
    {
        $before = null === $id ? [] : $this->records->get($section, $id) ?? [];
        $pre    = new RecordPreSave($section, $id, new Change($before, [...$before, ...$values]));
        $this->dispatcher->dispatch($section, $pre);

        if (! $pre->proceeds()) {
            $message = $pre->stopMessage() ?? self::HELD;
            $this->post($section, $id, $pre->change(), Outcome::Stopped, $message);

            return new SaveOutcome(Outcome::Stopped, $id, $message, $pre->errors(), $pre->warnings(), $pre->flags());
        }

        return $this->commit($pre);
    }

    /**
     * Writes the record and runs the main stage inside a transaction, rolling
     * both back when a listener fails.
     */
    private function commit(RecordPreSave $pre): SaveOutcome
    {
        $snapshot = $this->records->snapshot();
        $change   = $pre->change();
        $saved    = $this->records->write($pre->section, $pre->id, $change->after);
        $failure  = $this->dispatcher->dispatch($pre->section, new RecordSave($pre->section, $saved, $change));
        if (null !== $failure) {
            $this->records->restore($snapshot);
            $this->post($pre->section, $pre->id, $change, Outcome::Failed, $failure);

            return new SaveOutcome(Outcome::Failed, $pre->id, $failure);
        }

        $this->post($pre->section, $saved, $change, Outcome::Done, '');

        return new SaveOutcome(Outcome::Done, $saved, '', [], $pre->warnings(), $pre->flags());
    }

    private function post(string $section, string|int|null $id, Change $change, Outcome $outcome, string $reason): void
    {
        $this->dispatcher->dispatch($section, new RecordPostSave($section, $id, $change, $outcome, $reason));
    }
}
