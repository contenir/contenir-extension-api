<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Handler\HandlerResult;
use Contenir\Extension\Api\Handler\ResultKind;
use Contenir\Extension\Api\Registration;
use Contenir\Extension\Api\Testing\InMemoryTemplates;
use JsonException;
use LogicException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;

use function json_encode;
use function sprintf;

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
     * a described screen as its title, and a template as it draws (the
     * in-memory host draws nothing around either).
     *
     * @throws JsonException  When a JSON result cannot be encoded.
     * @throws LogicException When the add-on has no such template.
     * @throws Throwable      What the template throws.
     */
    public function respond(HandlerResult $result, Registration $registration): ResponseInterface
    {
        $handle = $registration->handle;

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
            ResultKind::Template => $this->body(200, 'text/html', $this->template($result, $registration)),
        };
    }

    public function status(int $status): ResponseInterface
    {
        return $this->responses->createResponse($status);
    }

    /**
     * @throws LogicException When the add-on has no templates or no such template.
     * @throws Throwable      What the template throws.
     */
    private function template(HandlerResult $result, Registration $registration): string
    {
        $templates = $registration->module->templates
            ?? throw new LogicException(sprintf('Add-on "%s" has no templates', $registration->handle->value));
        $template = $result->template ?? throw new LogicException('A template result names no template');

        return (new InMemoryTemplates($registration->handle, $templates))->render($template->name, $template->values);
    }
}
