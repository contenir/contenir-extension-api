<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Cache;

use Contenir\Extension\Api\Event\AbstractPreEvent;
use Contenir\Extension\Api\Event\CoreEventInterface;

/**
 * Before the page cache is cleared.
 *
 * @api
 */
final class CachePreClear extends AbstractPreEvent implements CoreEventInterface
{
    /**
     * @param string $scope What is being cleared: "all", or a section's name
     */
    public function __construct(
        public readonly string $scope,
    ) {}
}
