<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Boundary;

use Contenir\Extension\Api\Boundary\Package;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class PackageTest extends TestCase
{
    #[Test]
    public function fallsBackForMissingFields(): void
    {
        $package = Package::fromComposer(['name' => 5, 'autoload' => ['psr-4' => 'src/']]);

        static::assertSame(['', 'library', [], false], [
            $package->name,
            $package->type,
            $package->namespaces,
            $package->isAddOn(),
        ]);
    }

    /**
     * @mago-expect lint:no-literal-namespace-string The namespaces are composer.json data under test.
     */
    #[Test]
    public function readsANameTypeAndNamespaces(): void
    {
        $package = Package::fromComposer([
            'name'     => 'acme/lib',
            'type'     => 'contenir-extension',
            'autoload' => [
                'psr-4' => ['Acme\\Lib\\' => 'src/', 'Acme\\More' => 'more/'],
                'psr-0' => ['Legacy_' => 'lib/'],
            ],
        ]);

        static::assertSame(
            ['acme/lib', 'contenir-extension', ['Acme\\Lib\\', 'Acme\\More\\', 'Legacy_\\'], true],
            [$package->name, $package->type, $package->namespaces, $package->isAddOn()],
        );
    }

    #[Test]
    public function readsNothingFromAnAutoloadThatIsNotAMap(): void
    {
        static::assertSame([], Package::fromComposer(['autoload' => 'src/'])->namespaces);
    }

    #[Test]
    public function readsTheDevelopmentAutoload(): void
    {
        static::assertSame(
            ['Acme\\Tests\\'],
            Package::fromComposer([
                'autoload-dev' => ['psr-4' => ['Acme\\Tests\\' => 'tests/']],
            ], 'autoload-dev')->namespaces,
        );
    }

    #[Test]
    public function skipsTheGlobalFallbackNamespace(): void
    {
        static::assertSame(
            ['Acme\\'],
            Package::fromComposer([
                'autoload' => ['psr-4' => ['' => 'src/', '\\' => 'x/', 'Acme\\' => 'acme/']],
            ])->namespaces,
        );
    }
}
