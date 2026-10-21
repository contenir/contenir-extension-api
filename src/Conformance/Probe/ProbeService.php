<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Override;

/**
 * The probe's ProbeServiceInterface.
 *
 * @api
 */
final readonly class ProbeService implements ProbeServiceInterface
{
    public function __construct(
        private string $madeBy,
    ) {}

    #[Override]
    public function madeBy(): string
    {
        return $this->madeBy;
    }
}
