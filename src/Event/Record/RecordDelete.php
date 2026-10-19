<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Record;

/**
 * Inside the transaction when a record is deleted.
 *
 * @api
 */
final readonly class RecordDelete extends AbstractRecordEvent {}
