<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Handler;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Answers a public request from outside the CMS, such as a payment
 * provider's or a mailing list's webhook. The host mounts it at
 * /extension/<handle>/<name> with no session, CSRF or ACL check, passes the
 * raw body, answers GET as well as POST, and limits how often it is called.
 *
 * Because nothing else guards it, the host calls verify() first and runs
 * handle() only when verify() accepts the request's signature or token.
 * A request verify() refuses is answered 401 without reaching handle().
 *
 * @api
 */
interface WebhookInterface
{
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): WebhookResponse;

    /**
     * Whether the request carries a valid signature or token.
     */
    public function verify(ServerRequestInterface $request, ExtensionContextInterface $context): bool;
}
