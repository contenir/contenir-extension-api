<?php

declare(strict_types=1);

namespace Contenir\Extension\Example\Tests\Integration;

use Contenir\Extension\Api\Conformance\AbstractAddOnConformanceTestCase;
use Contenir\Extension\Api\ExtensionInterface;
use Contenir\Extension\Example\ExampleExtension;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use PHPUnit\Framework\Attributes\Group;

use function dirname;

#[Group('integration')]
final class ExampleExtensionTest extends AbstractAddOnConformanceTestCase
{
    #[Override]
    protected function extension(): ExtensionInterface
    {
        return new ExampleExtension();
    }

    #[Override]
    protected function handle(): string
    {
        return 'example';
    }

    #[Override]
    protected function http(): Psr17Factory
    {
        return new Psr17Factory();
    }

    #[Override]
    protected function root(): string
    {
        return dirname(__DIR__, levels: 2);
    }
}
