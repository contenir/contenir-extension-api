<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Edge;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Diagnostic\FixInterface;
use Contenir\Extension\Api\Diagnostic\FixResult;
use Override;
use RuntimeException;

final class ExplodingFix implements FixInterface
{
    /**
     * @throws RuntimeException Always.
     */
    #[Override]
    public function apply(array $values, ExtensionContextInterface $context): FixResult
    {
        throw new RuntimeException('Fix crashed');
    }
}
