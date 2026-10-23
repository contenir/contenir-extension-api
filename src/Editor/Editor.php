<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Editor;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * Someone signed in to the CMS: their user id, as the host stores it, and
 * the name the CMS shows for them.
 *
 * @api
 */
final readonly class Editor
{
    /**
     * @throws InvalidDeclarationException When the name is empty.
     */
    public function __construct(
        public string|int $id,
        public string $name,
    ) {
        Names::text('An editor name', $name);
    }
}
