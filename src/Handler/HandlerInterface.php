<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Handler;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Answers an admin request a form can't make: an OAuth callback, a JSON
 * endpoint for a picker, a screen that is more than a form. The host mounts
 * it at /admin/extension/<handle>/<name> and checks the ACL resource
 * extension.<handle>.<name> before it runs.
 *
 * @api
 */
interface HandlerInterface
{
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): HandlerResult;
}
