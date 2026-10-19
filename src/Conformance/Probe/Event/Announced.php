<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe\Event;

use Contenir\Extension\Api\Event\EventInterface;

/**
 * The probe's own event.
 *
 * @api
 */
final readonly class Announced implements EventInterface
{
    public function __construct(
        public string $message,
    ) {}
}
