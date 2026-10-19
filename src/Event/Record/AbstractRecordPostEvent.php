<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Record;

use Contenir\Extension\Api\Event\AbstractPostEvent;
use Contenir\Extension\Api\Event\Change;
use Contenir\Extension\Api\Event\ChangeEventInterface;
use Contenir\Extension\Api\Event\CoreEventInterface;
use Contenir\Extension\Api\Event\Outcome;
use Contenir\Extension\Api\Url\RecordReference;
use Override;

/**
 * After a record action, whatever happened. By default its listeners run in
 * the background, so slow work (HTTP calls, cache, files, notifications)
 * never holds up the editor.
 *
 * @api
 */
abstract readonly class AbstractRecordPostEvent extends AbstractPostEvent implements
    ChangeEventInterface,
    CoreEventInterface
{
    public function __construct(
        public string $section,
        public string|int|null $id,
        private Change $change,
        Outcome $outcome = Outcome::Done,
        string $reason = '',
    ) {
        parent::__construct($outcome, $reason);
    }

    #[Override]
    public function change(): Change
    {
        return $this->change;
    }

    /**
     * The record, or null when it never got an id.
     */
    public function record(): ?RecordReference
    {
        return null === $this->id ? null : new RecordReference($this->section, $this->id);
    }
}
