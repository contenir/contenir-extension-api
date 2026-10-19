<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Cache;

use Contenir\Extension\Api\Event\AbstractPostEvent;
use Contenir\Extension\Api\Event\CoreEventInterface;
use Contenir\Extension\Api\Event\Outcome;

/**
 * After the page cache is cleared, whatever happened.
 *
 * @api
 */
final readonly class CachePostClear extends AbstractPostEvent implements CoreEventInterface
{
    public function __construct(
        public string $scope,
        Outcome $outcome = Outcome::Done,
        string $reason = '',
    ) {
        parent::__construct($outcome, $reason);
    }
}
