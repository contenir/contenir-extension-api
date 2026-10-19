<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\User;

use Contenir\Extension\Api\Event\AbstractPostEvent;
use Contenir\Extension\Api\Event\CoreEventInterface;
use Contenir\Extension\Api\Event\Outcome;

/**
 * After a sign-in that got past its password, whether a Pre listener stopped it or not.
 *
 * @api
 */
final readonly class UserPostSignIn extends AbstractPostEvent implements CoreEventInterface
{
    public function __construct(
        public string $username,
        public string $ip,
        Outcome $outcome = Outcome::Done,
        string $reason = '',
    ) {
        parent::__construct($outcome, $reason);
    }
}
