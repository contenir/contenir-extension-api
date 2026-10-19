<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Record;

/**
 * After a record's status changes, whatever happened.
 *
 * @api
 */
final readonly class RecordPostStatusChange extends AbstractRecordPostEvent {}
