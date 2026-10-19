<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Diagnostic\CheckInterface;
use Contenir\Extension\Api\Diagnostic\CheckResult;
use Contenir\Extension\Api\Diagnostic\OneClickFix;
use Override;

/**
 * Warns until the cache has been cleared, offering a one-click fix.
 *
 * @api
 */
final class CacheCheck implements CheckInterface
{
    #[Override]
    public function check(ExtensionContextInterface $context): CheckResult
    {
        if (Journal::from($context)->has('cache-cleared')) {
            return CheckResult::success();
        }

        return CheckResult::warning('The cache is stale', new OneClickFix('Clear it', ClearCache::class));
    }

    #[Override]
    public function label(): string
    {
        return 'Probe cache';
    }
}
