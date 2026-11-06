<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Panel;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * One thing a record panel says needs attention: a label such as "Description", a short detail such
 * as "Missing", and optionally a link that fixes it, which the host draws as a Fix button.
 *
 * @api
 */
final readonly class PanelRow
{
    /**
     * @throws InvalidDeclarationException When the label is empty.
     */
    public function __construct(
        public string $label,
        public string $detail = '',
        public ?Link $fix = null,
    ) {
        Names::text('A panel row label', $label);
    }
}
