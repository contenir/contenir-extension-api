<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Form;

/**
 * What a link field's value points at.
 *
 * @api
 */
enum LinkKind: string
{
    case Address   = 'address';
    case NewRecord = 'new';
    case Record    = 'record';
}
