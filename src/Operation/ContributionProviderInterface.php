<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Operation;

/**
 * Implemented by extensions that contribute non-button UI (aside text/links,
 * pre/post text, layout-nav links). Discovered via instanceof.
 *
 * @api
 */
interface ContributionProviderInterface
{
    /**
     * @return list<Contribution>
     */
    public function getContributions(): array;
}
