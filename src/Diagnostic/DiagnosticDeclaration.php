<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Diagnostic;

/**
 * A check an add-on registered.
 *
 * @api
 */
final readonly class DiagnosticDeclaration
{
    /**
     * @param class-string<CheckInterface> $class
     */
    public function __construct(
        public string $name,
        public string $class,
    ) {}
}
