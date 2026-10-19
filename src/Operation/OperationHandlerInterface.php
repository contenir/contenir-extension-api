<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Operation;

/**
 * Executes one extension operation. Implementations must be framework-agnostic:
 * they receive everything they need from the context and return an abstract
 * result the host translates to its own request/response cycle.
 *
 * @api
 */
interface OperationHandlerInterface
{
    public function handle(OperationContextInterface $context): OperationResultInterface;
}
