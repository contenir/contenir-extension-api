<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Module;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * One part of a meter's bar, such as the records with a high-impact problem. The host sizes the part by its
 * count against the others and lists it, with its count, in the legend.
 *
 * @api
 */
final readonly class MeterSegment
{
    /**
     * @throws InvalidDeclarationException When the label is empty or the count negative.
     */
    public function __construct(
        public string $label,
        public int $count,
        public MeterTone $tone,
    ) {
        Names::text('A meter segment label', $label);
        if ($count < 0) {
            throw InvalidDeclarationException::because("A meter segment's count must not be negative, {$count} given");
        }
    }
}
