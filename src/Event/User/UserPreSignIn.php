<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\User;

use Contenir\Extension\Api\Event\AbstractPreEvent;
use Contenir\Extension\Api\Event\CoreEventInterface;

/**
 * Before someone signs in, once their password has been accepted. A listener may stop the sign-in, for example to enforce an IP allow-list.
 *
 * @api
 */
final class UserPreSignIn extends AbstractPreEvent implements CoreEventInterface
{
    public function __construct(
        public readonly string $username,
        public readonly string $ip,
    ) {}
}
