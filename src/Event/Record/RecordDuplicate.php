<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Record;

/**
 * Inside the transaction when a record is duplicated; the id is the copy's.
 *
 * @api
 */
final readonly class RecordDuplicate extends AbstractRecordEvent {}
