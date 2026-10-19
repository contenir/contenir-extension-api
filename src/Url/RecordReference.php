<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Url;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * A record by its section and id: what an add-on stores when it needs a
 * record's public address later. The address itself is resolved on the
 * public side when it is needed, since only the site knows its routes.
 *
 * @api
 */
final readonly class RecordReference
{
    /**
     * @throws InvalidDeclarationException When the section is empty.
     */
    public function __construct(
        public string $section,
        public string|int $id,
    ) {
        Names::text('A record reference section', $section);
    }
}
