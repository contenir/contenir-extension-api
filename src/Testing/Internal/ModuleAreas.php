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
 * an existing one it adds to; else the module's own area; else, for a module
 * that only adds to existing areas, the first section it adds. Null for an
 * add-on that is not a module, or whose admin.xml can't be read or holds no
 * area or section; its handlers keep their own.
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

        $own   = null;
        $added = null;
        foreach (Elements::children($document->documentElement) as $area) {
            $section = self::section($area, $handler);
            if (null !== $section) {
                return $section;
            }

            if ('true' === $area->getAttribute(self::EXISTING)) {
                $added ??= self::firstSection($area);
                continue;
            }

            $own ??= $area->tagName;
        }

        return $own ?? $added;
    }

    /**
     * The resource of the first section in $area.
     */
    private static function firstSection(DOMElement $area): ?string
    {
        foreach (Elements::children(Elements::child($area, 'category')) as $category) {
            $section = Elements::children(Elements::child($category, 'section'))[0] ?? null;
            if (null !== $section) {
                return sprintf('%s.%s.%s', $area->tagName, $category->tagName, $section->tagName);
            }
        }

        return null;
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
