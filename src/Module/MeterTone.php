<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Module;

/**
 * How a part of a meter reads, for the host to colour it in its own calm palette.
 *
 * @api
 */
enum MeterTone: string
{
    case Good    = 'good';
    case Warning = 'warning';
    case Poor    = 'poor';
    case Neutral = 'neutral';
}
