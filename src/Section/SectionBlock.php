<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Section;

use Contenir\Extension\Api\Internal\Names;

use function in_array;

/**
 * An add-on's block on one section of the site: the element named after the
 * add-on inside the section's <extensions>, with what an add-on needs to
 * check it against the section, which is whether the section's records have
 * pages of their own and the fields it declares.
 *
 * @api
 */
final readonly class SectionBlock
{
    /**
     * @param string       $section  The section's path, group.category.section
     * @param list<string> $fields   The section's field names, in the order declared
     * @param bool         $linkable Whether the site marks the section as linkable
     *
     * @throws \Contenir\Extension\Api\Exception\InvalidDeclarationException When the section is empty.
     */
    public function __construct(
        public string $section,
        public Element $element,
        public array $fields = [],
        public bool $linkable = false,
    ) {
        Names::text('A section block section', $section);
    }

    public function hasField(string $name): bool
    {
        return in_array($name, $this->fields, strict: true);
    }
}
