<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Edge;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Diagnostic\CheckInterface;
use Contenir\Extension\Api\Diagnostic\CheckResult;
use Contenir\Extension\Api\Diagnostic\GuidedFix;
use Override;

final class ExplainCheck implements CheckInterface
{
    #[Override]
    public function check(ExtensionContextInterface $context): CheckResult
    {
        return CheckResult::failure('No cron entry', new GuidedFix('Set up cron', 'Add the line to the crontab.'));
    }

    #[Override]
    public function label(): string
    {
        return 'Cron';
    }
}
