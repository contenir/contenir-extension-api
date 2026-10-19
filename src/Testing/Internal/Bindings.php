<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use function array_map;
use function count;
use function usort;

/**
 * The listeners bound to each section and event, in the order they run.
 * Lines take positions 10, 20, 30… as they are written, per event; an
 * explicit order slots one in between, and ties keep the order written.
 *
 * @internal
 */
final class Bindings
{
    /**
     * @var array<string, array<string, list<Line>>> By section ('' for site-wide), then event class
     */
    private array $lines = [];

    public function add(?string $section, string $event, BoundListener $listener, ?int $order): void
    {
        $key   = $section ?? '';
        $lines = $this->lines[$key][$event] ?? [];

        $lines[]                   = new Line($order ?? ((count($lines) + 1) * 10), $listener);
        $this->lines[$key][$event] = $lines;
    }

    /**
     * @return list<BoundListener>
     */
    public function for(?string $section, string $event): array
    {
        $lines = $this->lines[$section ?? ''][$event] ?? [];
        usort($lines, static fn(Line $a, Line $b): int => $a->position <=> $b->position);

        return array_map(static fn(Line $line): BoundListener => $line->listener, $lines);
    }
}
