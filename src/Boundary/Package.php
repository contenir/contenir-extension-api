<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Boundary;

use function array_keys;
use function is_array;
use function is_string;
use function rtrim;

/**
 * What the boundary needs to know about a Composer package: its name, its
 * type, and the namespaces it autoloads.
 *
 * @api
 */
final readonly class Package
{
    public const string ADD_ON = 'contenir-extension';

    /**
     * @param list<string> $namespaces Each with a trailing backslash
     */
    public function __construct(
        public string $name,
        public string $type,
        public array $namespaces,
    ) {}

    /**
     * The package as a composer.json, or an entry in Composer's
     * installed.json, describes it. $section is "autoload" or
     * "autoload-dev".
     *
     * @param array<array-key, mixed> $data
     */
    public static function fromComposer(array $data, string $section = 'autoload'): self
    {
        return new self(
            is_string($data['name'] ?? null) ? $data['name'] : '',
            is_string($data['type'] ?? null) ? $data['type'] : 'library',
            self::namespaces(is_array($data[$section] ?? null) ? $data[$section] : []),
        );
    }

    /**
     * @param array<array-key, mixed> $autoload
     *
     * @return list<string>
     */
    private static function namespaces(array $autoload): array
    {
        $namespaces = [];
        foreach (['psr-4', 'psr-0'] as $standard) {
            $map = is_array($autoload[$standard] ?? null) ? $autoload[$standard] : [];
            foreach (array_keys($map) as $namespace) {
                $trimmed = rtrim((string) $namespace, characters: '\\');
                if ('' === $trimmed) {
                    continue;
                }

                $namespaces[] = "{$trimmed}\\";
            }
        }

        return $namespaces;
    }

    public function isAddOn(): bool
    {
        return self::ADD_ON === $this->type;
    }
}
