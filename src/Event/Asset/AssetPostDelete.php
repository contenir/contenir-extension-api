<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Asset;

use Contenir\Extension\Api\Event\AbstractPostEvent;
use Contenir\Extension\Api\Event\CoreEventInterface;
use Contenir\Extension\Api\Event\Outcome;

/**
 * After an asset is deleted, whatever happened.
 *
 * @api
 */
final readonly class AssetPostDelete extends AbstractPostEvent implements CoreEventInterface
{
    public function __construct(
        public string $path,
        Outcome $outcome = Outcome::Done,
        string $reason = '',
    ) {
        parent::__construct($outcome, $reason);
    }
}
