<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Service;

use Contenir\Extension\Api\Context\ExtensionContextInterface;

/**
 * Makes a service an add-on provides, with the add-on's context, so the
 * service can read the add-on's settings and log under its source. The host
 * asks for it only while the add-on is enabled, and makes it once.
 *
 * @api
 */
interface ServiceFactoryInterface
{
    /**
     * An instance of the service the factory was declared for.
     */
    public function create(ExtensionContextInterface $context): object;
}
