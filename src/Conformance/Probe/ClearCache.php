<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Diagnostic\FixInterface;
use Contenir\Extension\Api\Diagnostic\FixResult;
use Override;

/**
 * Clears the probe's cache in one click.
 *
 * @api
 */
final class ClearCache implements FixInterface
{
    #[Override]
    public function apply(array $values, ExtensionContextInterface $context): FixResult
    {
        Journal::from($context)->write('cache-cleared');

        return FixResult::applied();
    }
}
