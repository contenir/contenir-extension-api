<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Boundary;

use function implode;

/**
 * A boundary written as a Mago configuration layer, for the add-on's
 * mago.toml to extend: a perimeter rule for the add-on's code, an open rule
 * for its tests, and restrictions keeping the API's test tooling out of its
 * code.
 *
 * @api
 */
final readonly class GuardRules
{
    /**
     * Parts of the API for tests and tooling, which an add-on's tests may use
     * but its code may not.
     */
    private const array TEST_ONLY = [
        'Contenir\\Extension\\Api\\Boundary\\**',
        'Contenir\\Extension\\Api\\Conformance\\**',
        'Contenir\\Extension\\Api\\Internal\\**',
        'Contenir\\Extension\\Api\\Testing\\**',
    ];

    public function __construct(
        private Boundary $boundary,
    ) {}

    /**
     * @param list<string> $items
     */
    private static function list(array $items): string
    {
        return "[\n    '" . implode("',\n    '", $items) . "',\n]\n";
    }

    /**
     * @param list<string> $permits
     */
    private static function rule(string $namespace, array $permits): string
    {
        return "\n[[guard.perimeter.rules]]\nnamespace = '{$namespace}'\npermit = " . self::list($permits);
    }

    public function toToml(): string
    {
        $toml =
            "# Written by vendor/bin/extension-boundary from composer.json. Run it again after changing\n"
            . "# dependencies; the add-on's tests check this file is current.\n";

        foreach ($this->boundary->namespaces as $namespace) {
            $toml .= self::rule($namespace, $this->boundary->permits);
        }

        $testOnlyFrom = ['Contenir\\Extension\\Api\\**'];
        foreach ($this->boundary->testNamespaces as $namespace) {
            $toml           .= self::rule($namespace, ['@all']);
            $testOnlyFrom[] = "{$namespace}**";
        }

        foreach (self::TEST_ONLY as $dependency) {
            $toml .=
                "\n[[guard.perimeter.restrictions]]\ndependency = '{$dependency}'\nallow-from = "
                . self::list($testOnlyFrom);
        }

        return $toml;
    }
}
