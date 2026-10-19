<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Record;

/**
 * After a record is deleted, whatever happened.
 *
 * @api
 */
final readonly class RecordPostDelete extends AbstractRecordPostEvent {}
