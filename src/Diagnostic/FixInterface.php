<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Diagnostic;

use Contenir\Extension\Api\Context\ExtensionContextInterface;

/**
 * Puts right what a check found. The host runs it only for someone holding
 * extension.<handle>.fix, then runs the check again and shows its new result.
 *
 * @api
 */
interface FixInterface
{
    /**
     * @param array<string, mixed> $values What was entered in a guided fix's fields; empty for a one-click fix
     */
    public function apply(array $values, ExtensionContextInterface $context): FixResult;
}
