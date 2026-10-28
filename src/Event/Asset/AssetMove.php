<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Asset;

use Contenir\Extension\Api\Event\CoreEventInterface;

/**
 * Inside the action when an asset's file is moved or renamed.
 *
 * @api
 */
final readonly class AssetMove implements CoreEventInterface
{
    /**
     * @param string $from The asset's file within the site's storage before the move
     * @param string $to   Where the file is moved or renamed to
     */
    public function __construct(
        public string $from,
        public string $to,
    ) {}
}
