<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Edge;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Diagnostic\CheckInterface;
use Contenir\Extension\Api\Diagnostic\CheckResult;
use Contenir\Extension\Api\Diagnostic\OneClickFix;
use Override;

final class BrokenFixCheck implements CheckInterface
{
    #[Override]
    public function check(ExtensionContextInterface $context): CheckResult
    {
        return CheckResult::warning('Needs a fix', new OneClickFix('Fix it', ExplodingFix::class));
    }

    #[Override]
    public function label(): string
    {
        return 'Broken fix';
    }
}
