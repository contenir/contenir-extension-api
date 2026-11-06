<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\RecordList;

/**
 * What a {@see RecordList} row shows under one heading: lines of plain text, which the host escapes. A
 * cell with a link is the row's title, and a muted one recedes, such as a section's name.
 *
 * @api
 */
final readonly class RecordListCell
{
    /**
     * @param list<string> $lines One line each
     */
    public function __construct(
        public array $lines,
        public ?string $href = null,
        public bool $muted = false,
    ) {}

    public static function of(string $text, ?string $href = null, bool $muted = false): self
    {
        return new self([$text], $href, $muted);
    }
}
