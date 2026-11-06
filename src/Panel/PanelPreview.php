<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Panel;

/**
 * How a record reads as a search result, which a record panel shows under its image: the title and
 * description as plain text the host escapes. Either may be missing.
 *
 * @api
 */
final readonly class PanelPreview
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
    ) {}
}
