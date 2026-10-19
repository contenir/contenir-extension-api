<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Edge;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Diagnostic\CheckInterface;
use Contenir\Extension\Api\Diagnostic\CheckResult;
use Override;

final class ExplodingCheck implements CheckInterface
{
    /**
     * @throws EdgeFailure Always.
     */
    #[Override]
    public function check(ExtensionContextInterface $context): CheckResult
    {
        throw new EdgeFailure('Check crashed');
    }

    #[Override]
    public function label(): string
    {
        return 'Exploding';
    }
}
