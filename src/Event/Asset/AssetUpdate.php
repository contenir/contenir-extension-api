<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Asset;

use Contenir\Extension\Api\Event\CoreEventInterface;

/**
 * Inside the action when an asset is changed in place.
 *
 * @api
 */
final readonly class AssetUpdate implements CoreEventInterface
{
    /**
     * @param string                $path    The asset's main file within the site's storage
     * @param array<string, string> $changes What changed and what it is now, keyed by what changed: one of the
     *                                       asset's columns (`owner`, `caption`), `file` for a file replaced in
     *                                       place, or `variants` for variants generated or rebuilt
     */
    public function __construct(
        public string $path,
        public array $changes,
    ) {}
}
