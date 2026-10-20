<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Handler;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * A screen drawn from one of a module's templates, for the few screens that
 * can't be described as data (decision M1). The host draws the dashboard
 * around it, with $title in its header and breadcrumb.
 *
 * $name is the template's path within the module's templates folder, without
 * its .phtml extension: "fields" or "notification/editor". The template runs
 * with two variables: $values, as given here, and $view, the host's
 * Module\TemplateHelpersInterface. Its $tabs are what $view->tabs() draws,
 * as the host draws a described screen's; the template prints them under its
 * own header, so a module's template screens can share a row of tabs with its
 * described ones.
 *
 * @api
 */
final readonly class Template
{
    /**
     * @param array<string, mixed> $values
     * @param list<Tab>            $tabs
     *
     * @throws InvalidDeclarationException When the name is malformed or the title empty.
     */
    public function __construct(
        public string $name,
        public string $title,
        public array $values = [],
        public array $tabs = [],
    ) {
        Names::template($name);
        Names::text('A template title', $title);
    }
}
