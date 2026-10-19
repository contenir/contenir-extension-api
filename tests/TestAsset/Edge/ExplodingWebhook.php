<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Edge;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Handler\WebhookInterface;
use Contenir\Extension\Api\Handler\WebhookResponse;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class ExplodingWebhook implements WebhookInterface
{
    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): WebhookResponse
    {
        return WebhookResponse::empty();
    }

    /**
     * @throws RuntimeException Always.
     */
    #[Override]
    public function verify(ServerRequestInterface $request, ExtensionContextInterface $context): bool
    {
        throw new RuntimeException('Signature service down');
    }
}
