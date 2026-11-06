<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Event\ActionResult;
use Contenir\Extension\Api\Event\Outcome;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Handler\Privilege;
use Contenir\Extension\Api\Record\RecordUpdate;
use Contenir\Extension\Api\Record\RecordUpdateResult;
use Contenir\Extension\Api\Record\RecordUpdatesInterface;
use Override;

use function array_key_exists;
use function array_map;
use function count;
use function sprintf;

/**
 * The in-memory host's record updates, lent to add-ons as RecordUpdatesInterface. Each update is saved as
 * an editor's would be, so the Pre, main and Post listeners run, and a record that does not exist, or that
 * the signed-in editor may not update, is stopped without touching the others.
 *
 * @internal
 */
final readonly class HostRecordUpdates implements RecordUpdatesInterface
{
    public function __construct(
        private Records $records,
        private Dispatcher $dispatcher,
        private SignedIn $signedIn,
    ) {}

    /**
     * @param list<RecordUpdate> $updates
     *
     * @return list<RecordUpdateResult>
     *
     * @throws InvalidDeclarationException When there are more than MAX_RECORDS updates.
     */
    #[Override]
    public function update(array $updates): array
    {
        if (count($updates) > self::MAX_RECORDS) {
            throw InvalidDeclarationException::because(
                sprintf('One call takes at most %d record updates', self::MAX_RECORDS),
            );
        }

        return array_map($this->save(...), $updates);
    }

    private function save(RecordUpdate $update): RecordUpdateResult
    {
        $section = $update->record->section;
        $id      = $update->record->id;
        $before  = $this->records->get($section, $id);
        if (null === $before) {
            return $this->stopped($update, sprintf('There is no such record in %s', $section));
        }

        if (! $this->signedIn->may($section, Privilege::Update)) {
            return $this->stopped($update, sprintf('You may not update %s', $section));
        }

        $result = (new Saver($this->records, $this->dispatcher))->save($section, $id, $update->values);
        if (! $result->succeeded()) {
            return new RecordUpdateResult($update->record, $result);
        }

        $previous = [];
        foreach ($update->values as $field => $_) {
            $previous[$field] = array_key_exists($field, $before) ? $before[$field] : null;
        }

        return new RecordUpdateResult($update->record, $result, $previous);
    }

    private function stopped(RecordUpdate $update, string $message): RecordUpdateResult
    {
        return new RecordUpdateResult(
            $update->record,
            new ActionResult(Outcome::Stopped, $update->record->id, $message),
        );
    }
}
