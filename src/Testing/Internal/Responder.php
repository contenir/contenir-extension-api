<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Handler\Download;
use Contenir\Extension\Api\Handler\Fragment;
use Contenir\Extension\Api\Handler\HandlerResult;
use Contenir\Extension\Api\Handler\ResultKind;
use Contenir\Extension\Api\Handler\Template;
use Contenir\Extension\Api\Registration;
use Contenir\Extension\Api\Testing\InMemoryTemplates;
use JsonException;
use LogicException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;

use function assert;
use function fopen;
use function json_encode;
use function rewind;
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

    /**
     * A template, fragment or download result always carries what its kind needs; this is for the type checker.
     *
     * @throws LogicException Always.
     *
     * @codeCoverageIgnore Unreachable: HandlerResult's named constructors always set the template a kind needs.
     */
    private static function none(): never
    {
        throw new LogicException('A template result names no template');
    }

    public function body(int $status, string $contentType, string $body): ResponseInterface
    {
        return $this->status($status)
            ->withHeader('Content-Type', $contentType)
            ->withBody($this->streams->createStream($body));
    }

    /**
     * The file as the host sends one: an attachment named for it, not
     * cached, holding what its writer wrote.
     */
    public function download(int $status, Download $download): ResponseInterface
    {
        $file = fopen('php://memory', mode: 'w+b');
        assert(false !== $file, description: 'PHP opens a memory stream');
        ($download->writer)($file);
        rewind($file);

        return $this->status($status)
            ->withHeader('Content-Type', $download->mediaType)
            ->withHeader('Content-Disposition', "attachment; filename=\"{$download->filename}\"")
            ->withHeader('Cache-Control', 'no-store')
            ->withBody($this->streams->createStreamFromResource($file));
    }

    /**
     * JSON as JSON, a redirect to its URL or back to the add-on's settings,
     * a described screen as its title, a template or fragment as it draws
     * (the in-memory host draws nothing around any of them), and a download
     * as an attachment.
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
            ResultKind::Template => $this->body(
                200,
                'text/html',
                $this->template($result->template ?? self::none(), $registration),
            ),
            ResultKind::Fragment => $this->body(
                $result->status,
                'text/html',
                $this->template($result->fragment ?? self::none(), $registration),
            ),
            ResultKind::Download => $this->download(200, $result->download ?? self::none()),
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
    private function template(Template|Fragment $template, Registration $registration): string
    {
        $templates = $registration->module->templates
            ?? throw new LogicException(sprintf('Add-on "%s" has no templates', $registration->handle->value));

        return (new InMemoryTemplates($registration->handle, $templates))->render($template->name, $template->values);
    }
}
