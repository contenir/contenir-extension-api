<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Extension;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Diagnostic\CheckInterface;
use Contenir\Extension\Api\Diagnostic\CheckResult;
use Override;

final class ApiKeyCheck implements CheckInterface
{
    #[Override]
    public function check(ExtensionContextInterface $context): CheckResult
    {
        return CheckResult::success();
    }

    #[Override]
    public function label(): string
    {
        return 'API key';
    }
}
