<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Record;

use Contenir\Extension\Api\Event\Change;
use Contenir\Extension\Api\Event\ChangeEventInterface;
use Contenir\Extension\Api\Event\CoreEventInterface;
use Contenir\Extension\Api\Url\RecordReference;
use Override;

/**
 * A record action inside its database transaction. A listener may write its
 * own ext_<handle>_ rows here; if it throws, the whole action rolls back, so
 * the record and the add-on's data never disagree.
 *
 * @api
 */
abstract readonly class AbstractRecordEvent implements ChangeEventInterface, CoreEventInterface
{
    public function __construct(
        public string $section,
        public string|int $id,
        private Change $change,
    ) {}

    #[Override]
    public function change(): Change
    {
        return $this->change;
    }

    public function record(): RecordReference
    {
        return new RecordReference($this->section, $this->id);
    }
}
