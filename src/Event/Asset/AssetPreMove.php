<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Asset;

use Contenir\Extension\Api\Event\AbstractPreEvent;
use Contenir\Extension\Api\Event\CoreEventInterface;

/**
 * Before an asset's file is moved to another folder or renamed, with every path that names it.
 * A listener may stop it.
 *
 * @api
 */
final class AssetPreMove extends AbstractPreEvent implements CoreEventInterface
{
    /**
     * @param string $from The asset's file within the site's storage before the move
     * @param string $to   Where the file is moved or renamed to
     */
    public function __construct(
        public readonly string $from,
        public readonly string $to,
    ) {}
}
