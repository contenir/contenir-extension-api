<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Module;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * How a module stands, as the host draws it on its dashboard card in the same layout as the other cards that
 * measure something: a headline value and what it means, a caption beneath it, and one bar divided into
 * segments with a legend. The host chooses the colours from each part's tone.
 *
 * @api
 */
final readonly class Meter
{
    /**
     * @param string             $value   What the meter reads, such as "72"
     * @param string             $label   What the value means, such as "Needs work"
     * @param ?string            $caption One line of context, such as when it was last checked, or null for none
     * @param list<MeterSegment> $segments
     *
     * @throws InvalidDeclarationException When the value or label is empty or the caption is blank.
     */
    public function __construct(
        public string $value,
        public string $label,
        public MeterTone $tone,
        public ?string $caption = null,
        public array $segments = [],
    ) {
        Names::text('A meter value', $value);
        Names::text('A meter label', $label);
        if (null !== $caption) {
            Names::text('A meter caption', $caption);
        }
    }
}
