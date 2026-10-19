<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Extension;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Schedule\ScheduledTaskInterface;
use Override;

final class ResyncTask implements ScheduledTaskInterface
{
    #[Override]
    public function run(ExtensionContextInterface $context): void {}
}
