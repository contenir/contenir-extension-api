<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Diagnostic\FixInterface;
use Contenir\Extension\Api\Diagnostic\FixResult;
use Override;

use function is_string;

/**
 * Stores the key a guided fix was given.
 *
 * @api
 */
final class StoreKey implements FixInterface
{
    #[Override]
    public function apply(array $values, ExtensionContextInterface $context): FixResult
    {
        if (! is_string($values['key'] ?? null)) {
            return FixResult::failed('No key was entered');
        }

        Journal::from($context)->write("key:{$values['key']}");

        return FixResult::applied('The key is stored');
    }
}
