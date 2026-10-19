<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Extension;

use Contenir\Extension\Api\Operation\OperationContextInterface;
use Contenir\Extension\Api\Operation\OperationHandlerInterface;
use Contenir\Extension\Api\Operation\OperationResult;
use Contenir\Extension\Api\Operation\OperationResultInterface;
use Override;

final class PurgeHandler implements OperationHandlerInterface
{
    #[Override]
    public function handle(OperationContextInterface $context): OperationResultInterface
    {
        return OperationResult::message('success', 'Purged');
    }
}
