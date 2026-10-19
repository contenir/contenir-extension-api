<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Entry;

use Contenir\Extension\Api\Event\AbstractChangePreEvent;
use Contenir\Extension\Api\Event\Change;
use Contenir\Extension\Api\Event\CoreEventInterface;

/**
 * Before an entry's status changes, for example to complete or spam. The change holds the status field.
 *
 * @api
 */
final class EntryPreStatusChange extends AbstractChangePreEvent implements CoreEventInterface
{
    public function __construct(
        public readonly string $form,
        public readonly string|int $id,
        Change $change,
    ) {
        parent::__construct($change);
    }
}
