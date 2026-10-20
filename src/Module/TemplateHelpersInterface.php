<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Module;

/**
 * What a module's template may ask of the host, as $view. A template uses
 * these and the dashboard theme's markup and classes, never the host's own
 * view helpers, so the same template draws in any host.
 *
 * @api
 */
interface TemplateHelpersInterface
{
    /**
     * The URL of a file in the module's published assets.
     */
    public function asset(string $path): string;

    /**
     * $text made safe to print in HTML, as text or as a quoted attribute's
     * value.
     */
    public function escape(string $text): string;

    /**
     * An icon's SVG markup, by the name the host's icon set or the module's
     * own icons give it.
     */
    public function icon(string $name): string;

    /**
     * Another of the module's templates, drawn with $values.
     *
     * @param array<string, mixed> $values
     */
    public function render(string $template, array $values = []): string;

    /**
     * The hidden field a form posting to one of the module's handlers must
     * carry, ready to print.
     */
    public function token(): string;

    /**
     * The URL of one of the module's handlers, with $query appended.
     *
     * @param array<string, scalar> $query
     */
    public function url(string $handler, array $query = []): string;
}
