<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Operation;

/**
 * Implemented by extensions that expose operations. Discovered by the host via
 * instanceof, so extensions without operations need not implement it.
 *
 * @api
 */
interface OperationProviderInterface
{
    /**
     * @return list<Operation>
     */
    public function getOperations(): array;
}
