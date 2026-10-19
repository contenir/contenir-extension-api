<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Migration;

use Contenir\Extension\Api\Event\AbstractPostEvent;
use Contenir\Extension\Api\Event\CoreEventInterface;
use Contenir\Extension\Api\Migration\Target;

/**
 * After migrations have run against a database.
 *
 * @api
 */
final readonly class MigrationsPostRun extends AbstractPostEvent implements CoreEventInterface
{
    /**
     * @param list<string> $applied The migrations that ran, in order
     */
    public function __construct(
        public Target $target,
        public array $applied,
    ) {
        parent::__construct();
    }
}
