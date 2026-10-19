<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Record;

/**
 * After a record's position among its siblings changes, whatever happened.
 *
 * @api
 */
final readonly class RecordPostReorder extends AbstractRecordPostEvent {}
