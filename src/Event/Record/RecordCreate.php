<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Record;

/**
 * Inside the transaction when a record is created.
 *
 * @api
 */
final readonly class RecordCreate extends AbstractRecordEvent {}
