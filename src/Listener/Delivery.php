<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Listener;

/**
 * When a listener runs. Pre and main-stage listeners are part of the action,
 * so they always run immediately. Post listeners are queued for the job
 * runner by default, so a slow or failing call never holds up or breaks the
 * action; one that is fast and local can ask to run immediately.
 *
 * @api
 */
enum Delivery: string
{
    case Background = 'background';
    case Immediate  = 'immediate';
}
