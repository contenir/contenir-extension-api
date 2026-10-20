<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Internal\Names;
use Contenir\Extension\Api\Module\TemplateHelpersInterface;
use LogicException;
use Override;
use Throwable;

use function htmlspecialchars;
use function http_build_query;
use function is_file;
use function ltrim;
use function ob_end_clean;
use function ob_get_clean;
use function ob_start;
use function sprintf;

use const ENT_HTML5;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * Draws a module's templates as a host would, with plain helpers in place of
 * the host's: URLs under /admin/extension/<handle>/ and /cms/ext/<handle>/, a
 * fixed form token, and an empty SVG naming each icon. A module's tests use
 * it to draw a template on its own.
 *
 * @api
 */
final readonly class InMemoryTemplates implements TemplateHelpersInterface
{
    public const string FORM_FIELD = '<input type="hidden" name="token" value="in-memory">';

    public function __construct(
        private Handle $handle,
        private string $path,
    ) {}

    /**
     * Runs the template with only $values and $view in scope.
     *
     * @param array<string, mixed> $values
     *
     * @throws Throwable What the template throws.
     *
     * @mago-expect analysis:unused-parameter The template reads $values and $view.
     */
    private static function draw(string $file, array $values, TemplateHelpersInterface $view): string
    {
        ob_start();
        try {
            include $file;
        } catch (Throwable $failure) {
            ob_end_clean();

            throw $failure;
        }

        return (string) ob_get_clean();
    }

    #[Override]
    public function asset(string $path): string
    {
        return sprintf('/cms/ext/%s/%s', $this->handle->value, ltrim($path, characters: '/'));
    }

    #[Override]
    public function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, encoding: 'UTF-8');
    }

    #[Override]
    public function icon(string $name): string
    {
        return sprintf('<svg class="icon" data-icon="%s" aria-hidden="true"></svg>', $this->escape($name));
    }

    /**
     * @throws InvalidDeclarationException When the name is malformed.
     * @throws LogicException              When the module has no such template.
     * @throws Throwable                   What the template throws.
     */
    #[Override]
    public function render(string $template, array $values = []): string
    {
        $file = sprintf('%s/%s.phtml', $this->path, Names::template($template));
        if (! is_file($file)) {
            throw new LogicException(sprintf('Module "%s" has no template "%s"', $this->handle->value, $template));
        }

        return self::draw($file, $values, $this);
    }

    #[Override]
    public function token(): string
    {
        return self::FORM_FIELD;
    }

    #[Override]
    public function url(string $handler, array $query = []): string
    {
        $url = sprintf('/admin/extension/%s/%s', $this->handle->value, $handler);

        return [] === $query ? $url : "{$url}?" . http_build_query($query);
    }
}
