<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Record;

/**
 * After a record is saved, whatever happened.
 *
 * @api
 */
final readonly class RecordPostSave extends AbstractRecordPostEvent {}
