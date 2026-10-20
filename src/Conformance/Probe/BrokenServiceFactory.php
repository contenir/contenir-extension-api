<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Service\ServiceFactoryInterface;
use Override;
use RuntimeException;

/**
 * A factory that throws, as one whose service can't connect might.
 *
 * @api
 */
final class BrokenServiceFactory implements ServiceFactoryInterface
{
    /**
     * @throws RuntimeException Always.
     */
    #[Override]
    public function create(ExtensionContextInterface $context): object
    {
        throw new RuntimeException('The service could not be made');
    }
}
