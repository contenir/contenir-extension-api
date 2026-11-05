<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Site;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;

use function preg_match;
use function sprintf;

/**
 * A path an add-on may post to on the site: one of its own /api/ endpoints,
 * without dots, so it can't climb out of them, or a query. Hosts check it
 * before signing anything.
 *
 * @api
 */
final class SitePath
{
    /**
     * @throws InvalidDeclarationException When the path isn't under /api/.
     */
    public static function check(string $path): string
    {
        if (1 !== preg_match('~^/api(/[a-zA-Z0-9_-]+)+$~D', $path)) {
            throw InvalidDeclarationException::because(sprintf('"%s" is not one of the site\'s /api/ paths', $path));
        }

        return $path;
    }
}
