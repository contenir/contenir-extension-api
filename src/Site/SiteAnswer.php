<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Site;

/**
 * What the site answered a request with.
 *
 * @api
 */
final readonly class SiteAnswer
{
    public function __construct(
        public int $status,
        public string $body,
    ) {}
}
