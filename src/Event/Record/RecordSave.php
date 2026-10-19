<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Record;

/**
 * Inside the transaction when a record is saved.
 *
 * @api
 */
final readonly class RecordSave extends AbstractRecordEvent {}
