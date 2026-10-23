<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use DOMElement;

/**
 * An admin.xml element's child elements, skipping text and comments.
 *
 * @internal
 */
final class Elements
{
    /**
     * The first child of $parent named $name, if any.
     */
    public static function child(?DOMElement $parent, string $name): ?DOMElement
    {
        foreach (self::children($parent) as $child) {
            if ($name === $child->tagName) {
                return $child;
            }
        }

        return null;
    }

    /**
     * @return list<DOMElement>
     */
    public static function children(?DOMElement $parent): array
    {
        $children = [];
        foreach ($parent->childNodes ?? [] as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $children[] = $node;
        }

        return $children;
    }
}
