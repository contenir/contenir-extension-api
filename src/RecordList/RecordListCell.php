<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\RecordList;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;

use function count;
use function sprintf;

/**
 * What a {@see RecordList} row shows under one heading: lines of plain text, which the host escapes, each
 * with an optional badge before it. A cell with a link is the row's title, and a muted one recedes, such as
 * a section's name.
 *
 * @api
 */
final readonly class RecordListCell
{
    /**
     * @param list<string>                  $lines  One line each
     * @param list<RecordListBadge|null>    $badges A badge before each line, or null for none; empty for no badges
     *
     * @throws InvalidDeclarationException When there are badges but not one for each line.
     */
    public function __construct(
        public array $lines,
        public ?string $href = null,
        public bool $muted = false,
        public array $badges = [],
    ) {
        if ([] !== $badges && count($badges) !== count($lines)) {
            throw InvalidDeclarationException::because(
                sprintf(
                    'A record list cell needs a badge entry for each of its %d lines, %d given',
                    count($lines),
                    count($badges),
                ),
            );
        }
    }

    public static function of(string $text, ?string $href = null, bool $muted = false): self
    {
        return new self([$text], $href, $muted);
    }
}
