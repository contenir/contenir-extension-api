<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Boundary;

/**
 * composer.json and installed.json contents for boundary tests.
 */
final class ComposerFixtures
{
    /**
     * @param array<string, string> $require
     *
     * @return array<string, mixed>
     */
    public static function addOn(
        array $require = [],
        string $type = 'contenir-extension',
        ?array $autoload = null,
    ): array {
        return [
            'name'         => 'contenir-extension/events',
            'type'         => $type,
            'require'      => $require,
            'autoload'     => $autoload ?? ['psr-4' => ['Contenir\\Extension\\Events\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Contenir\\Extension\\Events\\Tests\\' => 'tests/']],
        ];
    }

    /**
     * Composer 2's installed.json with a library, another add-on and a
     * package with no namespace.
     *
     * @return array<string, mixed>
     */
    public static function installed(): array
    {
        return [
            'packages' => [
                'not a package',
                [
                    'name'     => 'php-db/phpdb',
                    'type'     => 'library',
                    'autoload' => ['psr-4' => ['PhpDb\\' => 'src/', 'PhpDb\\Sql\\' => 'sql/']],
                ],
                [
                    'name'     => 'contenir-extension/commerce',
                    'type'     => 'contenir-extension',
                    'autoload' => ['psr-4' => ['Contenir\\Extension\\Commerce\\' => 'src/']],
                ],
                [
                    'name'     => 'acme/functions',
                    'type'     => 'library',
                    'autoload' => ['files' => ['functions.php']],
                ],
                [
                    'name'     => 'contenir/contenir-cms',
                    'type'     => 'project',
                    'autoload' => ['psr-4' => ['Contenir\\Cms\\' => 'library/']],
                ],
            ],
        ];
    }
}
