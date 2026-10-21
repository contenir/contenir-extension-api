<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Operation\OperationContextInterface;
use Contenir\Extension\Api\Operation\OperationHandlerInterface;
use Contenir\Extension\Api\Operation\OperationResultInterface;
use Override;
use RuntimeException;

/**
 * An operation that throws.
 *
 * @api
 */
final class BrokenOperation implements OperationHandlerInterface
{
    /**
     * @throws RuntimeException Always.
     */
    #[Override]
    public function handle(OperationContextInterface $context): OperationResultInterface
    {
        throw new RuntimeException('The operation broke');
    }
}
