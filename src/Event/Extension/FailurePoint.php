<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Extension;

/**
 * Where an add-on's code failed.
 *
 * @api
 */
enum FailurePoint: string
{
    case Check     = 'check';
    case Fix       = 'fix';
    case Handler   = 'handler';
    case Listener  = 'listener';
    case Menu      = 'menu';
    case Meter     = 'meter';
    case Operation = 'operation';
    case Panel     = 'panel';
    case Schedule  = 'schedule';
    case Section   = 'section';
    case Service   = 'service';
    case Webhook   = 'webhook';
}
