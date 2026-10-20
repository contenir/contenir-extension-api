<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Service\ServiceFactoryInterface;
use Override;

/**
 * Makes the ProbeService from the context it is given, so a test sees whose
 * context that was.
 *
 * @api
 */
final class ProbeServiceFactory implements ServiceFactoryInterface
{
    #[Override]
    public function create(ExtensionContextInterface $context): ProbeService
    {
        return new ProbeService($context->handle()->value);
    }
}
