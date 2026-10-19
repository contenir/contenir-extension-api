<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Handler;

use JsonException;

use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * What a public webhook answers with.
 *
 * @api
 */
final readonly class WebhookResponse
{
    private function __construct(
        public int $status,
        public string $body,
        public string $contentType,
    ) {}

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
