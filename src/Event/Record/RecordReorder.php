<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Record;

/**
 * Inside the transaction when a record's position among its siblings changes.
 *
 * @api
 */
final readonly class RecordReorder extends AbstractRecordEvent {}
