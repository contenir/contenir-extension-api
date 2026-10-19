<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Record;

/**
 * Inside the transaction when a record moves to another parent.
 *
 * @api
 */
final readonly class RecordMove extends AbstractRecordEvent {}
