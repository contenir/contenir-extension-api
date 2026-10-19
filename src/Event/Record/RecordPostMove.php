<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Record;

/**
 * After a record moves to another parent, whatever happened.
 *
 * @api
 */
final readonly class RecordPostMove extends AbstractRecordPostEvent {}
