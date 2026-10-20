<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Handler;

use function in_array;
use function strtoupper;

/**
 * What a person must be allowed to do on a handler's permission for the
 * handler to run. A handler that declares none needs View to read (GET or
 * HEAD) and Update for anything else.
 *
 * @api
 */
enum Privilege: string
{
    case View    = 'view';
    case Add     = 'add';
    case Update  = 'update';
    case Delete  = 'delete';
    case Approve = 'approve';
    case Import  = 'import';
    case Export  = 'export';

    /**
     * What a request with $method needs when the handler declares nothing.
     */
    public static function forMethod(string $method): self
    {
        return in_array(strtoupper($method), ['GET', 'HEAD'], strict: true) ? self::View : self::Update;
    }
}
