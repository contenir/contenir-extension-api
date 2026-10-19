<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Operation;

/**
 * Implemented by extensions with transient fields to overlay on the row.
 * Discovered via instanceof.
 *
 * @api
 */
interface RowHydratorProviderInterface
{
    /**
     * @return list<class-string<RowHydratorInterface>>
     */
    public function getRowHydrators(): array;
}
