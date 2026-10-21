<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Handler;

use Closure;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;

/**
 * What an admin handler answers with: a redirect with messages, JSON, a
 * described screen, a file to download, or, for a module, one of its
 * templates, drawn inside the dashboard or alone as a fragment. The host
 * turns it into its own response.
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
        public ?Template $template = null,
        public ?Fragment $fragment = null,
        public ?Download $download = null,
    ) {}

    /**
     * Back to the add-on's settings section.
     */
    public static function back(): self
    {
        return new self(ResultKind::Redirect);
    }

    /**
     * A file for the browser to save as $filename: the host streams what
     * $writer writes, as an attachment that isn't cached. Messages on it are
     * shown on the next screen.
     *
     * @param Closure(resource): void $writer Writes the file to the stream it is given
     *
     * @throws InvalidDeclarationException When the name is empty or could break a header, or the media type is empty.
     */
    public static function download(string $filename, string $mediaType, Closure $writer): self
    {
        return new self(ResultKind::Download, download: new Download($filename, $mediaType, $writer));
    }

    /**
     * One of the module's templates, drawn alone as HTML, with $status, for a
     * script on one of its screens to put in place. Only a module that
     * declared templates may answer with one; any other handler's fragment
     * answer fails as the handler would.
     *
     * @param array<string, mixed> $values What the template sees as $values
     *
     * @throws InvalidDeclarationException When the name is malformed.
     */
    public static function fragment(string $name, array $values = [], int $status = 200): self
    {
        return new self(ResultKind::Fragment, status: $status, fragment: new Fragment($name, $values));
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
     * One of the module's templates, drawn inside the dashboard with $title
     * in its header. Only a module that declared templates may answer with
     * one; any other handler's template answer fails as the handler would.
     * Its $tabs are what the template prints with $view->tabs(), drawn as a
     * described screen's are, and its $parent, when named, the page the
     * breadcrumb shows it under.
     *
     * @param array<string, mixed> $values What the template sees as $values
     * @param list<Tab>            $tabs
     *
     * @throws InvalidDeclarationException When the name is malformed or the title empty.
     */
    public static function template(
        string $name,
        string $title,
        array $values = [],
        array $tabs = [],
        ?ParentPage $parent = null,
    ): self {
        return new self(ResultKind::Template, template: new Template($name, $title, $values, $tabs, $parent));
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
            $this->template,
            $this->fragment,
            $this->download,
        );
    }
}
