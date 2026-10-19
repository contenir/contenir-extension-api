<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Boundary;

use Contenir\Extension\Api\Boundary\Boundary;
use Contenir\Extension\Api\Boundary\BoundaryException;
use Contenir\Extension\Api\Tests\TestAsset\Boundary\ComposerFixtures;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class BoundaryTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function forbiddenProvider(): array
    {
        return [
            'the legacy CMS'     => ['contenir/contenir-cms'],
            'a CMS package'      => ['contenir/cms-core'],
            'the admin'          => ['contenir/contenir-admin'],
            'Laminas MVC'        => ['laminas/laminas-mvc'],
            'a Laminas MVC part' => ['laminas/laminas-mvc-plugins'],
            'Mezzio'             => ['mezzio/mezzio'],
            'ZF1'                => ['zendframework/zendframework1'],
            'ZF1 elsewhere'      => ['zf1/zend-db'],
            'Shardj ZF1'         => ['shardj/zf1-future'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function ignoredProvider(): array
    {
        return [
            'PHP'          => ['php'],
            'an extension' => ['ext-json'],
            'a library'    => ['lib-icu'],
            'Composer'     => ['composer-plugin-api'],
            'the API'      => ['contenir/extension-api'],
        ];
    }

    #[Test]
    public function allowsALibraryItRequires(): void
    {
        $boundary = Boundary::forAddOn(ComposerFixtures::addOn([
            'php-db/phpdb' => '^0.6',
        ]), ComposerFixtures::installed());

        static::assertSame(['PhpDb\\**', 'PhpDb\\Sql\\**'], [
            $boundary->permits[8] ?? null,
            $boundary->permits[9] ?? null,
        ]);
    }

    #[Test]
    public function allowsOnlyTheEventAndApiNamespacesOfAnotherAddOn(): void
    {
        $boundary = Boundary::forAddOn(
            ComposerFixtures::addOn(['contenir-extension/commerce' => '^1.0']),
            ComposerFixtures::installed(),
        );

        static::assertSame(
            ['Contenir\\Extension\\Commerce\\Event\\**', 'Contenir\\Extension\\Commerce\\Api\\**'],
            [$boundary->permits[8] ?? null, $boundary->permits[9] ?? null],
        );
    }

    #[Test]
    public function allowsTheApiThePsrInterfacesAndItsOwnCode(): void
    {
        $boundary = Boundary::forAddOn(ComposerFixtures::addOn(), ComposerFixtures::installed());

        static::assertSame(
            [
                '@native',
                'Contenir\\Extension\\Api\\**',
                'Psr\\Clock\\**',
                'Psr\\Container\\**',
                'Psr\\EventDispatcher\\**',
                'Psr\\Http\\Message\\**',
                'Psr\\Log\\**',
                'Contenir\\Extension\\Events\\**',
            ],
            $boundary->permits,
        );
    }

    #[Test]
    #[DataProvider('ignoredProvider')]
    public function ignoresPlatformRequirementsAndTheApi(string $name): void
    {
        static::assertCount(8, Boundary::forAddOn(ComposerFixtures::addOn([$name => '*']), [])->permits);
    }

    #[Test]
    public function knowsItsOwnAndItsTestsNamespaces(): void
    {
        $boundary = Boundary::forAddOn(ComposerFixtures::addOn(), ComposerFixtures::installed());

        static::assertSame(
            [
                ['Contenir\\Extension\\Events\\'],
                ['Contenir\\Extension\\Events\\Tests\\'],
            ],
            [$boundary->namespaces, $boundary->testNamespaces],
        );
    }

    #[Test]
    public function listsEachPermitOnce(): void
    {
        $boundary = Boundary::forAddOn(
            ComposerFixtures::addOn(['psr/log' => '^3.0']),
            ['packages' => [['name' => 'psr/log', 'autoload' => ['psr-4' => ['Psr\\Log\\' => 'src']]]]],
        );

        static::assertCount(8, $boundary->permits);
    }

    #[Test]
    public function looksPastAnIgnoredRequirement(): void
    {
        $boundary = Boundary::forAddOn(
            ComposerFixtures::addOn(['php' => '^8.3', 'php-db/phpdb' => '^0.6']),
            ComposerFixtures::installed(),
        );

        static::assertSame('PhpDb\\**', $boundary->permits[8] ?? null);
    }

    #[Test]
    public function readsARequireBlockThatIsNotAList(): void
    {
        $composer            = ComposerFixtures::addOn();
        $composer['require'] = 'php';

        static::assertCount(8, Boundary::forAddOn($composer, [])->permits);
    }

    #[Test]
    public function readsComposerOnesInstalledList(): void
    {
        $boundary = Boundary::forAddOn(
            ComposerFixtures::addOn(['php-db/phpdb' => '^0.6']),
            [['name' => 'php-db/phpdb', 'autoload' => ['psr-4' => ['PhpDb\\' => 'src/']]]],
        );

        static::assertSame('PhpDb\\**', $boundary->permits[8] ?? null);
    }

    #[Test]
    public function refusesAnAddOnWithNoNamespace(): void
    {
        $this->expectException(BoundaryException::class);
        $this->expectExceptionMessage('contenir-extension/events autoloads no namespace');

        Boundary::forAddOn(ComposerFixtures::addOn(autoload: []), []);
    }

    #[Test]
    public function refusesANamespaceOutsideTheAddOnNamespace(): void
    {
        $this->expectException(BoundaryException::class);
        $this->expectExceptionMessage(
            'Namespace Acme\\Events\\ must be Contenir\\Extension\\<Package>\\, other than Api\\',
        );

        Boundary::forAddOn(ComposerFixtures::addOn(autoload: ['psr-4' => ['Acme\\Events\\' => 'src/']]), []);
    }

    #[Test]
    public function refusesAPackageThatIsNotAnAddOn(): void
    {
        $this->expectException(BoundaryException::class);
        $this->expectExceptionMessage(
            'contenir-extension/events is not an add-on: its type must be "contenir-extension"',
        );

        Boundary::forAddOn(ComposerFixtures::addOn(type: 'library'), []);
    }

    #[Test]
    public function refusesAPackageThatIsNotInstalled(): void
    {
        $this->expectException(BoundaryException::class);
        $this->expectExceptionMessage('acme/missing is required but not installed: run composer install first');

        Boundary::forAddOn(ComposerFixtures::addOn(['acme/missing' => '*']), ComposerFixtures::installed());
    }

    #[Test]
    #[DataProvider('forbiddenProvider')]
    public function refusesAPackageThatReachesIntoACms(string $name): void
    {
        $this->expectException(BoundaryException::class);
        $this->expectExceptionMessage("An add-on may not require {$name}");

        Boundary::forAddOn(ComposerFixtures::addOn([$name => '*']), ComposerFixtures::installed());
    }

    #[Test]
    public function refusesAPackageWithNoNamespace(): void
    {
        $this->expectException(BoundaryException::class);
        $this->expectExceptionMessage('acme/functions autoloads no namespace an add-on could be allowed to use');

        Boundary::forAddOn(ComposerFixtures::addOn(['acme/functions' => '*']), ComposerFixtures::installed());
    }

    #[Test]
    public function refusesEveryNamespaceOutsideTheAddOnNamespace(): void
    {
        $this->expectException(BoundaryException::class);
        $this->expectExceptionMessage('Namespace Acme\\Events\\ must be');

        Boundary::forAddOn(
            ComposerFixtures::addOn(autoload: ['psr-4' => [
                'Contenir\\Extension\\Events\\' => 'src/',
                'Acme\\Events\\'                => 'acme/',
            ]]),
            [],
        );
    }

    #[Test]
    public function refusesTheApisOwnNamespace(): void
    {
        $this->expectException(BoundaryException::class);
        $this->expectExceptionMessage('Namespace Contenir\\Extension\\Api\\ must be');

        Boundary::forAddOn(ComposerFixtures::addOn(autoload: ['psr-4' => [
            'Contenir\\Extension\\Api\\' => 'src/',
        ]]), []);
    }

    #[Test]
    public function reportsEveryProblemAtOnce(): void
    {
        try {
            Boundary::forAddOn(
                ComposerFixtures::addOn(['mezzio/mezzio' => '*', 'acme/missing' => '*'], 'library'),
                ComposerFixtures::installed(),
            );
            static::fail('The boundary should have been refused');
        } catch (BoundaryException $exception) {
            static::assertSame(
                [
                    'contenir-extension/events is not an add-on: its type must be "contenir-extension"',
                    'An add-on may not require mezzio/mezzio',
                    'acme/missing is required but not installed: run composer install first',
                ],
                $exception->problems,
            );
        }
    }
}
