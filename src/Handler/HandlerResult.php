<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Handler;

/**
 * What an admin handler answers with: a redirect with messages, JSON, or a
 * described screen. The host turns it into its own response.
 *
 * @api
 */
final readonly class HandlerResult
{
    /**
     * @param list<array{level: string, text: string}> $messages
     *
     * @mago-expect lint:excessive-parameter-list Private; the named constructors set what each kind of result needs.
     */
    private function __construct(
        public ResultKind $kind,
        public ?string $url = null,
        public mixed $json = null,
        public int $status = 200,
        public ?Screen $screen = null,
        public array $messages = [],
    ) {}

    /**
     * Back to the add-on's settings section.
     */
    public static function back(): self
    {
        return new self(ResultKind::Redirect);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return new self(ResultKind::Json, json: $data, status: $status);
    }

    /**
     * To another URL, such as a provider's authorisation page.
     */
    public static function redirect(string $url): self
    {
        return new self(ResultKind::Redirect, url: $url);
    }

    public static function screen(Screen $screen): self
    {
        return new self(ResultKind::Screen, screen: $screen);
    }

    /**
     * The same result with a message for the next screen; $level is one of
     * success, info, warning or danger.
     */
    public function withMessage(string $level, string $text): self
    {
        return new self(
            $this->kind,
            $this->url,
            $this->json,
            $this->status,
            $this->screen,
            [...$this->messages, ['level' => $level, 'text' => $text]],
        );
    }
}
