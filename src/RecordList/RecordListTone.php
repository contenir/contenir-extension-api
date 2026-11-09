<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\RecordList;

/**
 * How serious a badge on a {@see RecordList} line reads, for the host to colour in its own palette.
 *
 * @api
 */
enum RecordListTone: string
{
    case Danger  = 'danger';
    case Warning = 'warning';
    case Info    = 'info';
}
