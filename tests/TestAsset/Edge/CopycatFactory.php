<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Edge;

use Contenir\Extension\Api\Conformance\Probe\WrongServiceInterface;
use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Service\ServiceFactoryInterface;
use Override;

final class CopycatFactory implements ServiceFactoryInterface
{
    #[Override]
    public function create(ExtensionContextInterface $context): WrongServiceInterface
    {
        return new class implements WrongServiceInterface {};
    }
}
