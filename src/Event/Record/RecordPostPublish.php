<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Record;

/**
 * After a record is published. A CMS that publishes as it saves fires this beside Save on the same action, whatever happened.
 *
 * @api
 */
final readonly class RecordPostPublish extends AbstractRecordPostEvent {}
