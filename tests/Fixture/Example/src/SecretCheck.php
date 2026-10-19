<?php

declare(strict_types=1);

namespace Contenir\Extension\Example;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Diagnostic\CheckInterface;
use Contenir\Extension\Api\Diagnostic\CheckResult;
use Contenir\Extension\Api\Diagnostic\GuidedFix;
use Override;

/**
 * Fails until the signing secret is set, since every ping is refused
 * without it. The fix explains where to set it, then checks again.
 *
 * @api
 */
final class SecretCheck implements CheckInterface
{
    #[Override]
    public function check(ExtensionContextInterface $context): CheckResult
    {
        if ('' !== Ping::secret($context)) {
            return CheckResult::success('The signing secret is set');
        }

        return CheckResult::failure('No signing secret is set, so every ping is refused', new GuidedFix(
            label: 'Set the secret',
            explanation: 'Copy the signing secret from the service and enter it in Site Settings > Example.',
        ));
    }

    #[Override]
    public function label(): string
    {
        return 'Example signing secret';
    }
}
