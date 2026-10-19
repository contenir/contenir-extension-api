<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Record;

use Contenir\Extension\Api\Event\AbstractChangePreEvent;
use Contenir\Extension\Api\Event\Change;
use Contenir\Extension\Api\Event\CoreEventInterface;
use Contenir\Extension\Api\Url\RecordReference;

/**
 * Before a record action: the section, the record's id (null when it is not
 * created yet), and its values before and as they are about to be written.
 *
 * @api
 */
abstract class AbstractRecordPreEvent extends AbstractChangePreEvent implements CoreEventInterface
{
    public function __construct(
        public readonly string $section,
        public readonly string|int|null $id,
        Change $change,
    ) {
        parent::__construct($change);
    }

    /**
     * The record, or null when it has no id yet.
     */
    public function record(): ?RecordReference
    {
        return null === $this->id ? null : new RecordReference($this->section, $this->id);
    }
}
