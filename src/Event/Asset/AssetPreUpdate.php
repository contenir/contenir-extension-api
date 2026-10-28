<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Asset;

use Contenir\Extension\Api\Event\AbstractPreEvent;
use Contenir\Extension\Api\Event\CoreEventInterface;

/**
 * Before an asset is changed in place: its details or owner, its file replaced, or its variants
 * generated. A listener may stop it.
 *
 * @api
 */
final class AssetPreUpdate extends AbstractPreEvent implements CoreEventInterface
{
    /**
     * @param string                $path    The asset's main file within the site's storage
     * @param array<string, string> $changes What changed and what it is now, keyed by what changed: one of the
     *                                       asset's columns (`owner`, `caption`), `file` for a file replaced in
     *                                       place, or `variants` for variants generated or rebuilt
     */
    public function __construct(
        public readonly string $path,
        public readonly array $changes,
    ) {}
}
