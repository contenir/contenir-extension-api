<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Panel;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * What a record panel shows: a title, then optionally an image, rows of what needs attention, paragraphs
 * of plain text, links, and a menu of actions. The host escapes all of it, since a panel quotes the
 * record's own values, which editors type: no markup reaches the page.
 *
 * A panel that sets only the title, paragraphs and links is drawn as it always was; the image, rows
 * and menu are drawn only when given. A host that predates them leaves them out.
 *
 * @api
 */
final readonly class Panel
{
    /**
     * @param list<string>   $paragraphs
     * @param list<Link>     $links
     * @param list<PanelRow> $rows        What needs attention, each with its own Fix link
     * @param list<Link>     $menu        The actions menu, drawn as one "Actions" button
     *
     * @throws InvalidDeclarationException When the title is empty.
     *
     * @mago-expect lint:excessive-parameter-list One parameter per part a panel can show.
     */
    public function __construct(
        public string $title,
        public array $paragraphs = [],
        public array $links = [],
        public ?PanelImage $image = null,
        public array $rows = [],
        public array $menu = [],
    ) {
        Names::text('A panel title', $title);
    }
}
