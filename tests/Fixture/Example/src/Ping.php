<?php

declare(strict_types=1);

namespace Contenir\Extension\Example;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Handler\WebhookInterface;
use Contenir\Extension\Api\Handler\WebhookResponse;
use JsonException;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;

use function hash_equals;
use function hash_hmac;

/**
 * Answers pings from the service, which signs each body with the shared
 * secret: an HMAC-SHA256 of the body, in X-Example-Signature.
 *
 * @api
 */
final class Ping implements WebhookInterface
{
    public const string HEADER = 'X-Example-Signature';

    /**
     * The signing secret, or an empty string when there is none.
     */
    public static function secret(ExtensionContextInterface $context): string
    {
        try {
            return $context->services()->has(Secret::class) ? $context->services()->get(Secret::class)->value : '';
        } catch (ContainerExceptionInterface) {
            return '';
        }
    }

    /**
     * @throws JsonException Never: the answer is a constant.
     */
    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): WebhookResponse
    {
        return WebhookResponse::json(['pong' => true]);
    }

    #[Override]
    public function verify(ServerRequestInterface $request, ExtensionContextInterface $context): bool
    {
        $secret = self::secret($context);
        if ('' === $secret) {
            return false;
        }

        $expected = hash_hmac('sha256', (string) $request->getBody(), $secret);

        return hash_equals($expected, $request->getHeaderLine(self::HEADER));
    }
}
