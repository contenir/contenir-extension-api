<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Boundary;

use function array_keys;
use function is_array;
use function sprintf;

/**
 * What an add-on's requirements let it use: every namespace of a library it
 * requires, only the Event\ and Api\ namespaces of another add-on, and
 * nothing from a CMS or its framework.
 *
 * @api
 */
final readonly class Dependencies
{
    /**
     * @param array<string, Package> $installed By name
     */
    private function __construct(
        private array $installed,
    ) {}

    /**
     * The packages Composer installed, from vendor/composer/installed.json.
     *
     * @param array<array-key, mixed> $installed
     *
     * @mago-expect analysis:mixed-assignment installed.json is untyped; each entry is checked here.
     */
    public static function fromInstalled(array $installed): self
    {
        $entries  = is_array($installed['packages'] ?? null) ? $installed['packages'] : $installed;
        $packages = [];
        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $package                  = Package::fromComposer($entry);
            $packages[$package->name] = $package;
        }

        return new self($packages);
    }

    /**
     * @return list<string>
     */
    private static function permitsFor(Package $package): array
    {
        $permits = [];
        foreach ($package->namespaces as $namespace) {
            if (! $package->isAddOn()) {
                $permits[] = "{$namespace}**";
                continue;
            }

            $permits[] = "{$namespace}Event\\**";
            $permits[] = "{$namespace}Api\\**";
        }

        return $permits;
    }

    private static function problemWith(string $name, ?Package $package): string
    {
        return match (true) {
            Requirement::isForbidden($name) => sprintf('An add-on may not require %s', $name),
            null === $package => sprintf('%s is required but not installed: run composer install first', $name),
            default => sprintf('%s autoloads no namespace an add-on could be allowed to use', $name),
        };
    }

    /**
     * What the add-on's requirements let it use, as Mago guard paths, and
     * what is wrong with any of them.
     *
     * @param array<array-key, mixed> $composer The add-on's composer.json
     *
     * @return array{list<string>, list<string>} The permits, then the problems
     */
    public function allow(array $composer): array
    {
        $permits  = [];
        $problems = [];
        $require  = is_array($composer['require'] ?? null) ? $composer['require'] : [];
        foreach (array_keys($require) as $name) {
            $name = (string) $name;
            if (Requirement::isIgnored($name)) {
                continue;
            }

            $package = $this->installed[$name] ?? null;
            if (Requirement::isForbidden($name) || null === $package || [] === $package->namespaces) {
                $problems[] = self::problemWith($name, $package);
                continue;
            }

            foreach (self::permitsFor($package) as $permit) {
                $permits[] = $permit;
            }
        }

        return [$permits, $problems];
    }
}
