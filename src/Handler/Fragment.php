<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Handler;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * Part of a screen drawn from one of a module's templates, alone, for a
 * script on that screen to put in place: a new row in an editor, or the
 * body of a dialog. The host draws nothing around it.
 *
 * $name is the template's path within the module's templates folder, without
 * its .phtml extension, and the template runs as a Template's does, with
 * $values and $view.
 *
 * @api
 */
final readonly class Fragment
{
    /**
     * @param array<string, mixed> $values
     *
     * @throws InvalidDeclarationException When the name is malformed.
     */
    public function __construct(
        public string $name,
        public array $values = [],
    ) {
        Names::template($name);
    }
}
