<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Asset;

use Contenir\Extension\Api\Event\AbstractPreEvent;
use Contenir\Extension\Api\Event\CoreEventInterface;

/**
 * Before an asset is deleted. A listener may stop it, for example after a virus scan.
 *
 * @api
 */
final class AssetPreDelete extends AbstractPreEvent implements CoreEventInterface
{
    /**
     * @param string $path The asset's path within the site's storage
     */
    public function __construct(
        public readonly string $path,
    ) {}
}
