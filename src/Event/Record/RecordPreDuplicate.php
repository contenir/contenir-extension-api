<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Record;

/**
 * Before a record is duplicated; the id is the copy's.
 *
 * @api
 */
final class RecordPreDuplicate extends AbstractRecordPreEvent {}
