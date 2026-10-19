<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Diagnostic;

/**
 * How a check came out. A failure is serious: the host also shows it on the
 * dashboard.
 *
 * @api
 */
enum Status: string
{
    case Failure = 'failure';
    case Skipped = 'skipped';
    case Success = 'success';
    case Warning = 'warning';
}
