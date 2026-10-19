<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Handler\HandlerResult;
use Contenir\Extension\Api\Handler\ResultKind;
use JsonException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * Turns what handlers answer with into responses.
 *
 * @internal
 */
final readonly class Responder
{
    public function __construct(
        private ResponseFactoryInterface $responses,
        private StreamFactoryInterface $streams,
    ) {}

    public function body(int $status, string $contentType, string $body): ResponseInterface
    {
        return $this->status($status)
            ->withHeader('Content-Type', $contentType)
            ->withBody($this->streams->createStream($body));
    }

    /**
     * JSON as JSON, a redirect to its URL or back to the add-on's settings,
     * and a described screen as its title (the in-memory host draws nothing).
     *
     * @throws JsonException When a JSON result cannot be encoded.
     */
    public function respond(HandlerResult $result, Handle $handle): ResponseInterface
    {
        return match ($result->kind) {
            ResultKind::Json => $this->body(
                $result->status,
                'application/json',
                json_encode($result->json, JSON_THROW_ON_ERROR),
            ),
            ResultKind::Redirect => $this->status(302)->withHeader(
                'Location',
                $result->url ?? "/admin/settings/{$handle->value}",
            ),
            ResultKind::Screen   => $this->body(200, 'text/plain', $result->screen->title ?? ''),
        };
    }

    public function status(int $status): ResponseInterface
    {
        return $this->responses->createResponse($status);
    }
}
