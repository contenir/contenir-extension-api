<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Asset;

use Contenir\Extension\Api\Event\AbstractPostEvent;
use Contenir\Extension\Api\Event\CoreEventInterface;
use Contenir\Extension\Api\Event\Outcome;

/**
 * After an asset's file is moved or renamed, whatever happened.
 *
 * @api
 */
final readonly class AssetPostMove extends AbstractPostEvent implements CoreEventInterface
{
    /**
     * @param string $from The asset's file within the site's storage before the move
     * @param string $to   Where the file is moved or renamed to
     */
    public function __construct(
        public string $from,
        public string $to,
        Outcome $outcome = Outcome::Done,
        string $reason = '',
    ) {
        parent::__construct($outcome, $reason);
    }
}
