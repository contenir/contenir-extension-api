<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Record;

/**
 * Inside the transaction when a record's status changes.
 *
 * @api
 */
final readonly class RecordStatusChange extends AbstractRecordEvent {}
