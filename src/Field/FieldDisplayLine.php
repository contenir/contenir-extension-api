<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Field;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * One line of how a field reads on a record's View page: a label, its text, and optionally a note the host
 * draws beside it as an info label, such as "From content" for a value the record doesn't hold itself.
 *
 * @api
 */
final readonly class FieldDisplayLine
{
    /**
     * @throws InvalidDeclarationException When the label or text is empty.
     */
    public function __construct(
        public string $label,
        public string $text,
        public ?string $note = null,
    ) {
        Names::text('A field display label', $label);
        Names::text('A field display text', $text);
    }
}
