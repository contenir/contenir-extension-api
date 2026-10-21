<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Boundary;

use function in_array;
use function str_starts_with;

/**
 * How the boundary treats a name in an add-on's require block.
 *
 * @api
 */
final class Requirement
{
    /**
     * Packages that would let an add-on reach into a CMS or its framework.
     */
    private const array FORBIDDEN = [
        'contenir/contenir-admin',
        'contenir/contenir-cms',
        'contenir/cms',
        'laminas/laminas-mvc',
        'mezzio/',
        'shardj/zf1',
        'zendframework/',
        'zf1/',
    ];

    /**
     * Requirements that are not packages, or that the boundary always allows.
     */
    private const array IGNORED = ['php', 'contenir/contenir-extension-api', 'contenir/extension-api'];

    private const array PLATFORM = ['ext-', 'lib-', 'composer-'];

    /**
     * Whether requiring $name would let the add-on reach into a CMS or its
     * framework.
     */
    public static function isForbidden(string $name): bool
    {
        return self::startsWithAny($name, self::FORBIDDEN);
    }

    /**
     * Whether $name is PHP, an extension or library of the platform, or the
     * API itself.
     */
    public static function isIgnored(string $name): bool
    {
        return in_array($name, self::IGNORED, strict: true) || self::startsWithAny($name, self::PLATFORM);
    }

    /**
     * @param list<string> $prefixes
     */
    private static function startsWithAny(string $name, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
