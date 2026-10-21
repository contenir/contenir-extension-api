<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Handler;

use Closure;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use JsonException;

use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * What a public webhook answers with: nothing, JSON, text, or a file to
 * download, which the host streams as a handler's download.
 *
 * @api
 */
final readonly class WebhookResponse
{
    private function __construct(
        public int $status,
        public string $body,
        public string $contentType,
        public ?Download $download = null,
    ) {}

    /**
     * A file for the browser to save as $filename, streamed by the host from
     * what $writer writes, as an attachment that isn't cached.
     *
     * @param Closure(resource): void $writer Writes the file to the stream it is given
     *
     * @throws InvalidDeclarationException When the name is empty or could break a header, or the media type is empty.
     */
    public static function download(string $filename, string $mediaType, Closure $writer, int $status = 200): self
    {
        return new self($status, '', $mediaType, new Download($filename, $mediaType, $writer));
    }

    public static function empty(int $status = 204): self
    {
        return new self($status, '', 'text/plain');
    }

    /**
     * @throws JsonException When $data cannot be encoded.
     */
    public static function json(mixed $data, int $status = 200): self
    {
        return new self($status, json_encode($data, JSON_THROW_ON_ERROR), 'application/json');
    }

    public static function text(string $body, int $status = 200): self
    {
        return new self($status, $body, 'text/plain');
    }
}
