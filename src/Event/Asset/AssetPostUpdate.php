<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Asset;

use Contenir\Extension\Api\Event\AbstractPostEvent;
use Contenir\Extension\Api\Event\CoreEventInterface;
use Contenir\Extension\Api\Event\Outcome;

/**
 * After an asset is changed in place, whatever happened.
 *
 * @api
 */
final readonly class AssetPostUpdate extends AbstractPostEvent implements CoreEventInterface
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
        Outcome $outcome = Outcome::Done,
        string $reason = '',
    ) {
        parent::__construct($outcome, $reason);
    }
}
