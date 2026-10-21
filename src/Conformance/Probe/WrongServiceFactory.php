<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Service\ServiceFactoryInterface;
use Override;

/**
 * A factory that makes a ProbeService where a WrongServiceInterface was
 * declared.
 *
 * @api
 */
final class WrongServiceFactory implements ServiceFactoryInterface
{
    #[Override]
    public function create(ExtensionContextInterface $context): object
    {
        return new ProbeService($context->handle()->value);
    }
}
