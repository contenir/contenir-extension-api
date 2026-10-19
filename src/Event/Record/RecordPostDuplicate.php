<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Record;

/**
 * After a record is duplicated; the id is the copy's, whatever happened.
 *
 * @api
 */
final readonly class RecordPostDuplicate extends AbstractRecordPostEvent {}
