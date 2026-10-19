<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Event\Extension\FailurePoint;
use Contenir\Extension\Api\Handler\WebhookInterface;
use Contenir\Extension\Api\Registration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Answers requests to add-ons' public webhooks, each only once it has
 * verified its caller.
 *
 * @internal
 */
final readonly class Webhooks
{
    public function __construct(
        private Runtime $runtime,
        private Responder $responder,
    ) {}

    public function answer(Registration $registration, string $name, ServerRequestInterface $request): ResponseInterface
    {
        $declaration = $registration->webhooks[$name] ?? null;
        if (null === $declaration) {
            return $this->responder->status(404);
        }

        try {
            $webhook = $this->runtime->make($declaration->class, WebhookInterface::class);
            $context = $this->runtime->context($registration->handle);
            if (! $webhook->verify($request, $context)) {
                return $this->responder->status(401);
            }

            $answer = $webhook->handle($request, $context);

            return $this->responder->body($answer->status, $answer->contentType, $answer->body);
        } catch (Throwable $failure) {
            $this->runtime->report($registration->handle, FailurePoint::Webhook, $name, $failure);

            return $this->responder->status(500);
        }
    }
}
