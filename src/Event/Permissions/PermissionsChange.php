<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Permissions;

use Contenir\Extension\Api\Event\Change;
use Contenir\Extension\Api\Event\ChangeEventInterface;
use Contenir\Extension\Api\Event\CoreEventInterface;
use Override;

/**
 * Inside the transaction when a role's permissions change; the values are its privileges.
 *
 * @api
 */
final readonly class PermissionsChange implements ChangeEventInterface, CoreEventInterface
{
    public function __construct(
        public string $role,
        private Change $change,
    ) {}

    #[Override]
    public function change(): Change
    {
        return $this->change;
    }
}
