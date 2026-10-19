<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Permissions;

use Contenir\Extension\Api\Event\AbstractChangePreEvent;
use Contenir\Extension\Api\Event\Change;
use Contenir\Extension\Api\Event\CoreEventInterface;

/**
 * Before a role's permissions change; the values are its privileges. Listeners may change the values, add field errors, warn, flag or stop it.
 *
 * @api
 */
final class PermissionsPreChange extends AbstractChangePreEvent implements CoreEventInterface
{
    public function __construct(
        public readonly string $role,
        Change $change,
    ) {
        parent::__construct($change);
    }
}
