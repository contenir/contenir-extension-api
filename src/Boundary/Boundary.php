<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Boundary;

use function array_unique;
use function array_values;
use function sprintf;
use function str_starts_with;

/**
 * What an add-on's code may use (plan section 12 and P7): PHP itself, the
 * extension API, the PSR interfaces it names, the add-on's own namespaces,
 * the libraries its composer.json requires, and only the Event\ and Api\
 * namespaces of other add-ons it requires. Nothing from either CMS, ZF1,
 * Laminas MVC or Mezzio, and nothing it doesn't require itself.
 *
 * GuardRules writes it out for mago guard, which the add-on's CI runs. Its
 * tests stay unrestricted.
 *
 * @api
 */
final readonly class Boundary
{
    /**
     * Every add-on's classes live under this namespace.
     */
    public const string ADD_ON_NAMESPACE = 'Contenir\\Extension\\';

    /**
     * The API itself and the standard interfaces the plan allows: PSR-3,
     * PSR-7 and PSR-17, PSR-11, PSR-14 and PSR-20.
     */
    private const array ALWAYS = [
        '@native',
        'Contenir\\Extension\\Api\\**',
        'Psr\\Clock\\**',
        'Psr\\Container\\**',
        'Psr\\EventDispatcher\\**',
        'Psr\\Http\\Message\\**',
        'Psr\\Log\\**',
    ];

    /**
     * @param list<string> $namespaces     The add-on's own namespaces
     * @param list<string> $permits        What its code may use, as Mago guard paths
     * @param list<string> $testNamespaces Its tests' namespaces, which may use anything
     */
    public function __construct(
        public array $namespaces,
        public array $permits,
        public array $testNamespaces,
    ) {}

    /**
     * The boundary for the add-on $composer describes, given the packages
     * Composer installed.
     *
     * @param array<array-key, mixed> $composer  The add-on's composer.json
     * @param array<array-key, mixed> $installed vendor/composer/installed.json
     *
     * @throws BoundaryException When the add-on is malformed or requires something it may not use.
     */
    public static function forAddOn(array $composer, array $installed): self
    {
        $addOn = Package::fromComposer($composer);
        [$libraries, $missing] = Dependencies::fromInstalled($installed)->allow($composer);

        $problems = [...self::problemsWith($addOn), ...$missing];
        if ([] !== $problems) {
            throw new BoundaryException($problems);
        }

        $own = [];
        foreach ($addOn->namespaces as $namespace) {
            $own[] = "{$namespace}**";
        }

        return new self(
            $addOn->namespaces,
            array_values(array_unique([...self::ALWAYS, ...$own, ...$libraries])),
            Package::fromComposer($composer, 'autoload-dev')->namespaces,
        );
    }

    /**
     * @return list<string>
     */
    private static function problemsWith(Package $addOn): array
    {
        $problems = [];
        if (! $addOn->isAddOn()) {
            $problems[] = sprintf('%s is not an add-on: its type must be "%s"', $addOn->name, Package::ADD_ON);
        }

        if ([] === $addOn->namespaces) {
            $problems[] = sprintf('%s autoloads no namespace', $addOn->name);
        }

        foreach ($addOn->namespaces as $namespace) {
            if (
                str_starts_with($namespace, self::ADD_ON_NAMESPACE)
                && self::ADD_ON_NAMESPACE . 'Api\\' !== $namespace
            ) {
                continue;
            }

            $problems[] = sprintf(
                'Namespace %s must be %s<Package>\\, other than Api\\',
                $namespace,
                self::ADD_ON_NAMESPACE,
            );
        }

        return $problems;
    }
}
