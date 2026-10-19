<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\User;

use Contenir\Extension\Api\Event\AbstractPostEvent;
use Contenir\Extension\Api\Event\CoreEventInterface;
use Contenir\Extension\Api\Event\Outcome;

/**
 * After a sign-in is refused: a wrong username or password, or a locked account. Its outcome is always failed.
 *
 * @api
 */
final readonly class SignInPostFail extends AbstractPostEvent implements CoreEventInterface
{
    public function __construct(
        public string $username,
        public string $ip,
        string $reason,
    ) {
        parent::__construct(Outcome::Failed, $reason);
    }
}
