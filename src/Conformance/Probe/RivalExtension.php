<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\ExtensionInterface;
use Contenir\Extension\Api\Registry;
use Override;

/**
 * A second add-on providing the probe's ProbeServiceInterface, as a second CDN
 * add-on would provide the same purger.
 *
 * @api
 */
final readonly class RivalExtension implements ExtensionInterface
{
    public const string HANDLE = 'rival';

    #[Override]
    public function register(Registry $registry): void
    {
        $registry->provide(ProbeServiceInterface::class, ProbeServiceFactory::class);
    }
}
