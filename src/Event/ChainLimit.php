<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event;

/**
 * How far a chain of events fired from listeners may go (E4). A host's
 * dispatcher refuses an event that would make the chain deeper than DEPTH,
 * and an event already running in the chain, whatever the depth.
 *
 * @api
 */
final class ChainLimit
{
    public const int DEPTH = 8;
}
