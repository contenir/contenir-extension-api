<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Boundary;

use Contenir\Extension\Api\Boundary\Boundary;
use Contenir\Extension\Api\Boundary\GuardRules;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class GuardRulesTest extends TestCase
{
    #[Test]
    public function keepsTheTestToolingInsideTheApiWhenThereAreNoTests(): void
    {
        $toml = (new GuardRules(new Boundary(['Contenir\\Extension\\Events\\'], ['@native'], [])))->toToml();

        static::assertStringContainsString(
            "dependency = 'Contenir\\Extension\\Api\\Testing\\**'\nallow-from = [\n    'Contenir\\Extension\\Api\\**',\n]\n",
            $toml,
        );
    }

    #[Test]
    public function writesAPerimeterRuleForTheCodeAndAnOpenOneForTheTests(): void
    {
        $toml = (new GuardRules(
            new Boundary(
                ['Contenir\\Extension\\Events\\'],
                ['@native', 'Psr\\Log\\**'],
                [
                    'Contenir\\Extension\\Events\\Tests\\',
                ],
            ),
        ))->toToml();

        static::assertSame(
            <<<'TOML'
                # Written by vendor/bin/extension-boundary from composer.json. Run it again after changing
                # dependencies; the add-on's tests check this file is current.

                [[guard.perimeter.rules]]
                namespace = 'Contenir\Extension\Events\'
                permit = [
                    '@native',
                    'Psr\Log\**',
                ]

                [[guard.perimeter.rules]]
                namespace = 'Contenir\Extension\Events\Tests\'
                permit = [
                    '@all',
                ]

                [[guard.perimeter.restrictions]]
                dependency = 'Contenir\Extension\Api\Boundary\**'
                allow-from = [
                    'Contenir\Extension\Api\**',
                    'Contenir\Extension\Events\Tests\**',
                ]

                [[guard.perimeter.restrictions]]
                dependency = 'Contenir\Extension\Api\Conformance\**'
                allow-from = [
                    'Contenir\Extension\Api\**',
                    'Contenir\Extension\Events\Tests\**',
                ]

                [[guard.perimeter.restrictions]]
                dependency = 'Contenir\Extension\Api\Internal\**'
                allow-from = [
                    'Contenir\Extension\Api\**',
                    'Contenir\Extension\Events\Tests\**',
                ]

                [[guard.perimeter.restrictions]]
                dependency = 'Contenir\Extension\Api\Testing\**'
                allow-from = [
                    'Contenir\Extension\Api\**',
                    'Contenir\Extension\Events\Tests\**',
                ]

                TOML,
            $toml,
        );
    }
}
