<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Asset;

use Contenir\Extension\Api\Event\CoreEventInterface;

/**
 * Inside the action when an asset is deleted.
 *
 * @api
 */
final readonly class AssetDelete implements CoreEventInterface
{
    public function __construct(
        public string $path,
    ) {}
}
