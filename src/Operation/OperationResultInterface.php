<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Operation;

/**
 * The abstract outcome of an operation: zero or more user messages and an
 * optional redirect target. The host maps these onto its own messenger and
 * redirector.
 *
 * @api
 */
interface OperationResultInterface
{
    /**
     * @return list<array{level: string, text: string}>
     */
    public function messages(): array;

    /**
     * Route parameters for the host redirector, or null to use the host
     * default (back to the section view).
     *
     * @return array<string, mixed>|null
     */
    public function redirect(): ?array;
}
