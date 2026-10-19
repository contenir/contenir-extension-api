<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Asset;

use Contenir\Extension\Api\Event\CoreEventInterface;

/**
 * Inside the action when an asset is uploaded.
 *
 * @api
 */
final readonly class AssetUpload implements CoreEventInterface
{
    public function __construct(
        public string $path,
    ) {}
}
