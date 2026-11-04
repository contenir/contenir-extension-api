<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Section\Element;

/**
 * The elements inside each section's <extensions>, as the site's admin.xml
 * would hold them, with the fields each section declares.
 *
 * @internal
 */
final class SectionBlocks
{
    /**
     * @var array<string, array<string, Element>> By section, in the order first given, then by element name
     */
    private array $elements = [];

    /**
     * @var array<string, list<string>> By section
     */
    private array $fields = [];

    /**
     * @return list<string>
     */
    public function fields(string $section): array
    {
        return $this->fields[$section] ?? [];
    }

    /**
     * The element named $handle on each section that has one, by section.
     *
     * @return array<string, Element>
     */
    public function for(string $handle): array
    {
        $found = [];
        foreach ($this->elements as $section => $elements) {
            $element = $elements[$handle] ?? null;
            if (null === $element) {
                continue;
            }

            $found[$section] = $element;
        }

        return $found;
    }

    /**
     * Puts $element inside $section's <extensions>, replacing one of the same
     * name, and records the section's fields.
     *
     * @param list<string> $fields
     */
    public function put(string $section, Element $element, array $fields): void
    {
        $this->elements[$section][$element->name] = $element;
        $this->fields[$section]                   = $fields;
    }
}
