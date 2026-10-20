<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Panel;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * What a record panel shows: a title, paragraphs of plain text and links.
 * The host escapes all of it, since a panel quotes the record's own values,
 * which editors type: no markup reaches the page.
 *
 * @api
 */
final readonly class Panel
{
    /**
     * @param list<string> $paragraphs
     * @param list<Link>   $links
     *
     * @throws InvalidDeclarationException When the title is empty.
     */
    public function __construct(
        public string $title,
        public array $paragraphs = [],
        public array $links = [],
    ) {
        Names::text('A panel title', $title);
    }
}
