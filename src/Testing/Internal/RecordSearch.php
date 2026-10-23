<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Record\LinkableSection;
use Contenir\Extension\Api\Record\RecordSummary;
use Contenir\Extension\Api\Url\RecordReference;

use function array_merge;
use function array_slice;
use function in_array;
use function max;
use function mb_stripos;
use function strnatcasecmp;
use function usort;

/**
 * Finds the in-memory host's records as an editor does: by title or path,
 * and by the path they are at.
 *
 * @internal
 */
final class RecordSearch
{
    /**
     * The records in the linkable sections whose title or path holds $text,
     * section by section in the site's order and then by title, at most
     * $limit of them.
     *
     * @param array<string, LinkableSection> $linkable By section
     * @param list<string>                   $sections Those to search, or none for all
     *
     * @return list<RecordSummary>
     */
    public static function found(Records $records, array $linkable, string $text, array $sections, int $limit): array
    {
        $groups = [];
        foreach ($linkable as $name => $section) {
            if ([] !== $sections && ! in_array($section->section, $sections, strict: true)) {
                continue;
            }

            $groups[] = self::matching($records, $name, $text);
        }

        return array_slice(array_merge(...$groups), offset: 0, length: max(0, $limit));
    }

    public static function isPathFree(Records $records, string $path): bool
    {
        foreach ($records->all() as $rows) {
            foreach ($rows as $values) {
                if ($path === ($values['path'] ?? null)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * The records in $section whose title or path holds $text, by title.
     *
     * @return list<RecordSummary>
     */
    private static function matching(Records $records, string $section, string $text): array
    {
        $matches = [];
        foreach ($records->all()[$section] ?? [] as $id => $values) {
            $summary = RecordValues::summary(new RecordReference($section, $id), $values);
            if (false !== mb_stripos($summary->title, $text) || false !== mb_stripos($summary->path ?? '', $text)) {
                $matches[] = $summary;
            }
        }

        usort($matches, static fn(RecordSummary $a, RecordSummary $b): int => strnatcasecmp($a->title, $b->title));

        return $matches;
    }
}
