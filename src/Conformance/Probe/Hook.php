<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Handler\WebhookInterface;
use Contenir\Extension\Api\Handler\WebhookResponse;
use Override;
use Psr\Http\Message\ServerRequestInterface;

use function hash_equals;

/**
 * A public webhook that accepts only requests signed "valid".
 *
 * @api
 */
final class Hook implements WebhookInterface
{
    public const string HEADER = 'X-Probe-Signature';

    public const string SIGNATURE = 'valid';

    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): WebhookResponse
    {
        Journal::from($context)->write("hook:{$request->getMethod()}:{$request->getBody()}");

        return WebhookResponse::text('received', 202);
    }

    #[Override]
    public function verify(ServerRequestInterface $request, ExtensionContextInterface $context): bool
    {
        return hash_equals(self::SIGNATURE, $request->getHeaderLine(self::HEADER));
    }
}
