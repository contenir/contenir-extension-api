<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event;

/**
 * An event about values that change, such as a record's fields. Only these
 * events can be listened to with a field scope.
 *
 * @api
 */
interface ChangeEventInterface extends EventInterface
{
    public function change(): Change;
}
