<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

/**
 * The service the probe provides: it says which add-on's context made it.
 *
 * @api
 */
interface ProbeServiceInterface
{
    public function madeBy(): string;
}
