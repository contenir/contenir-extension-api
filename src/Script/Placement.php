<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Script;

/**
 * Where on a public page a script goes.
 *
 * @api
 */
enum Placement: string
{
    case BodyEnd   = 'body-end';
    case BodyStart = 'body-start';
    case Head      = 'head';
}
