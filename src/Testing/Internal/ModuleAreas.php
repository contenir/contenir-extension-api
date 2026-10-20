<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Registration;
use DOMDocument;
use DOMElement;

use function is_file;
use function sprintf;
use function trim;

use const LIBXML_NOERROR;
use const LIBXML_NOWARNING;

/**
 * Which permission guards a module's handler: the section whose <handler>
 * names it in the module's admin.xml, or else the module's area. Null for an
 * add-on that is not a module, or whose admin.xml can't be read or holds no
 * area; its handlers keep their own.
 *
 * @internal
 */
final class ModuleAreas
{
    public static function resource(Registration $registration, string $handler): ?string
    {
        $file = $registration->module->adminXml;
        if (null === $file || ! is_file($file)) {
            return null;
        }

        $document = new DOMDocument();
        $document->load($file, LIBXML_NOERROR | LIBXML_NOWARNING);

        $area = self::children($document->documentElement)[0] ?? null;
        if (null === $area) {
            return null;
        }

        foreach (self::children(self::child($area, 'category')) as $category) {
            foreach (self::children(self::child($category, 'section')) as $section) {
                $named = self::child($section, 'handler');
                if (null !== $named && trim($named->textContent) === $handler) {
                    return sprintf('%s.%s.%s', $area->tagName, $category->tagName, $section->tagName);
                }
            }
        }

        return $area->tagName;
    }

    private static function child(?DOMElement $parent, string $name): ?DOMElement
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
    private static function children(?DOMElement $parent): array
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
