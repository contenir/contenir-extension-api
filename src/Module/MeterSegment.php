<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Module;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * One part of a meter's bar, such as the records with a high-impact problem. The host sizes the part by its
 * count against the others and lists it, with its count, in the legend, as a link where it has an address.
 *
 * @api
 */
final readonly class MeterSegment
{
    /**
     * @throws InvalidDeclarationException When the label or a given address is empty or the count negative.
     */
    public function __construct(
        public string $label,
        public int $count,
        public MeterTone $tone,
        public ?string $url = null,
    ) {
        Names::text('A meter segment label', $label);
        if (null !== $url) {
            Names::text('A meter segment address', $url);
        }
        if ($count < 0) {
            throw InvalidDeclarationException::because("A meter segment's count must not be negative, {$count} given");
        }
    }
}
