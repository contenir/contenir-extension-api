<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event;

/**
 * What happened to an action, as its Post event reports it.
 *
 * @api
 */
enum Outcome: string
{
    /**
     * The action went through.
     */
    case Done = 'done';

    /**
     * Something went wrong and the transaction was rolled back.
     */
    case Failed = 'failed';

    /**
     * A Pre listener stopped it, or a field had an error.
     */
    case Stopped = 'stopped';
}
