<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Diagnostic;

use Contenir\Extension\Api\Context\ExtensionContextInterface;

/**
 * A check in the update check. The host shows its result there, as a notice
 * on the add-on's settings screen and, when it fails, on the dashboard.
 *
 * @api
 */
interface CheckInterface
{
    public function check(ExtensionContextInterface $context): CheckResult;

    /**
     * What the check looks at, as the update check lists it.
     */
    public function label(): string;
}
