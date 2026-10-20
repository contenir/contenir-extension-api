<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

/**
 * The service the probe provides: it says which add-on made it, and the
 * note in that add-on's settings when it was made.
 *
 * @api
 */
interface NoteServiceInterface
{
    public function madeBy(): string;

    public function note(): string;
}
