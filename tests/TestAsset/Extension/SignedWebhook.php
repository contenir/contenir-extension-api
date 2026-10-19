<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Extension;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Handler\WebhookInterface;
use Contenir\Extension\Api\Handler\WebhookResponse;
use Override;
use Psr\Http\Message\ServerRequestInterface;

use function hash_equals;

final class SignedWebhook implements WebhookInterface
{
    public const string SIGNATURE = 'signed-by-the-provider';

    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): WebhookResponse
    {
        return WebhookResponse::empty();
    }

    #[Override]
    public function verify(ServerRequestInterface $request, ExtensionContextInterface $context): bool
    {
        return hash_equals(self::SIGNATURE, $request->getHeaderLine('X-Signature'));
    }
}
