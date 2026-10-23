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
 * names it in the module's admin.xml, whether in the module's own area or in
 * an existing one it adds to, or else the module's own area. Null for an
 * add-on that is not a module, or whose admin.xml can't be read or holds no
 * area of its own; its handlers keep their own.
 *
 * @internal
 */
final class ModuleAreas
{
    /**
     * The attribute marking an area in a module's admin.xml as one that
     * already exists, which the module adds categories to.
     */
    public const string EXISTING = 'existing';

    public static function resource(Registration $registration, string $handler): ?string
    {
        $file = $registration->module->adminXml;
        if (null === $file || ! is_file($file)) {
            return null;
        }

        $document = new DOMDocument();
        $document->load($file, LIBXML_NOERROR | LIBXML_NOWARNING);

        $own = null;
        foreach (Elements::children($document->documentElement) as $area) {
            $section = self::section($area, $handler);
            if (null !== $section) {
                return $section;
            }

            if (null === $own && 'true' !== $area->getAttribute(self::EXISTING)) {
                $own = $area->tagName;
            }
        }

        return $own;
    }

    /**
     * The resource of the section in $area whose <handler> names $handler.
     */
    private static function section(DOMElement $area, string $handler): ?string
    {
        foreach (Elements::children(Elements::child($area, 'category')) as $category) {
            foreach (Elements::children(Elements::child($category, 'section')) as $section) {
                $named = Elements::child($section, 'handler');
                if (null !== $named && trim($named->textContent) === $handler) {
                    return sprintf('%s.%s.%s', $area->tagName, $category->tagName, $section->tagName);
                }
            }
        }

        return null;
    }
}
