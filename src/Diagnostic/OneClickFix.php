<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Diagnostic;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * A fix applied with one button, needing nothing from the person pressing it.
 *
 * @api
 */
final readonly class OneClickFix
{
    /**
     * @param class-string<FixInterface> $handler
     *
     * @throws InvalidDeclarationException When the handler is not a fix.
     */
    public function __construct(
        public string $label,
        public string $handler,
    ) {
        Names::text('A fix label', $label);
        Names::implementing('Fix', $handler, FixInterface::class);
    }
}
