<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Handler;

/**
 * @api
 */
enum ResultKind
{
    case Json;
    case Redirect;
    case Screen;
    case Template;
    case Fragment;
    case Download;
}
