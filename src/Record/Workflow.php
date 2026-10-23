<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Record;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * One of the templates a section's records can be made with, such as a
 * page's "Standard page" or "Landing page": its id, as the record stores it,
 * and its name in the CMS.
 *
 * @api
 */
final readonly class Workflow
{
    /**
     * @throws InvalidDeclarationException When the id or title is empty.
     */
    public function __construct(
        public string $id,
        public string $title,
    ) {
        Names::text('A workflow id', $id);
        Names::text('A workflow title', $title);
    }
}
