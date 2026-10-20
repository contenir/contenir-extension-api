<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Edge;

use Contenir\Extension\Api\Conformance\Probe\WrongServiceInterface;
use Contenir\Extension\Api\ExtensionInterface;
use Contenir\Extension\Api\Registry;
use Override;

/**
 * Provides the probe's WrongServiceInterface, through a factory that works.
 */
final readonly class CopycatExtension implements ExtensionInterface
{
    #[Override]
    public function register(Registry $registry): void
    {
        $registry->provide(WrongServiceInterface::class, CopycatFactory::class);
    }
}
