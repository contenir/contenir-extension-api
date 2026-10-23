<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Record;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * A section the site marks as linkable, whose records an add-on may search,
 * link to and, when it is creatable, create: pages, news articles or
 * events. Its title names one of its records, such as "Page" or "News
 * article", and its workflows are the templates a new record can take.
 *
 * @api
 */
final readonly class LinkableSection
{
    /**
     * @param list<Workflow> $workflows
     *
     * @throws InvalidDeclarationException When the section or title is empty.
     */
    public function __construct(
        public string $section,
        public string $title,
        public bool $creatable = false,
        public array $workflows = [],
    ) {
        Names::text('A linkable section', $section);
        Names::text('A linkable section title', $title);
    }

    /**
     * The workflow with $id, or null when the section has none by that id.
     */
    public function workflow(string $id): ?Workflow
    {
        foreach ($this->workflows as $workflow) {
            if ($id === $workflow->id) {
                return $workflow;
            }
        }

        return null;
    }
}
