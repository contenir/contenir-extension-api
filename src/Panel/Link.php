<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Panel;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * A link in a record panel, such as to the add-on's report screen.
 *
 * @api
 */
final readonly class Link
{
    /**
     * @throws InvalidDeclarationException When the label or URL is empty.
     */
    public function __construct(
        public string $label,
        public string $url,
    ) {
        Names::text('A panel link label', $label);
        Names::text('A panel link URL', $url);
    }
}
