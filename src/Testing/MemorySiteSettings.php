<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing;

use Contenir\Extension\Api\Settings\SiteSettingsInterface;
use Override;

use function array_key_exists;
use function explode;
use function is_array;
use function is_scalar;

/**
 * Site settings held in memory, nested as a host's settings tree is, for a
 * test to lend under SiteSettingsInterface. A true setting reads as "1" and
 * a false one as "", as PHP prints them.
 *
 * @api
 */
final readonly class MemorySiteSettings implements SiteSettingsInterface
{
    /**
     * @param array<array-key, mixed> $settings
     */
    public function __construct(
        private array $settings = [],
    ) {}

    /**
     * @mago-expect analysis:mixed-assignment Settings are untyped until each step is checked.
     */
    #[Override]
    public function get(string $key): ?string
    {
        $node = $this->settings;
        foreach (explode('.', $key) as $part) {
            if (! is_array($node) || ! array_key_exists($part, $node)) {
                return null;
            }

            $node = $node[$part];
        }

        return is_scalar($node) ? (string) $node : null;
    }
}
