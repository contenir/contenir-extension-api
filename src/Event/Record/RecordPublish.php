<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Record;

/**
 * Inside the transaction when a record is published. A CMS that publishes as it saves fires this beside Save on the same action.
 *
 * @api
 */
final readonly class RecordPublish extends AbstractRecordEvent {}
