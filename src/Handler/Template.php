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
 * Module\TemplateHelpersInterface.
 *
 * @api
 */
final readonly class Template
{
    /**
     * @param array<string, mixed> $values
     *
     * @throws InvalidDeclarationException When the name is malformed or the title empty.
     */
    public function __construct(
        public string $name,
        public string $title,
        public array $values = [],
    ) {
        Names::template($name);
        Names::text('A template title', $title);
    }
}
