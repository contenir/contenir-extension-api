<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Record;

/**
 * After a record is created, whatever happened.
 *
 * @api
 */
final readonly class RecordPostCreate extends AbstractRecordPostEvent {}
