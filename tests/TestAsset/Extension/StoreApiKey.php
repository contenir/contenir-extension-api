<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Extension;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Diagnostic\FixInterface;
use Contenir\Extension\Api\Diagnostic\FixResult;
use Override;

final class StoreApiKey implements FixInterface
{
    #[Override]
    public function apply(array $values, ExtensionContextInterface $context): FixResult
    {
        return FixResult::applied();
    }
}
