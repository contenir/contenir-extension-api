<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Service;

/**
 * A service an add-on provides: the interface the host asks for, and the
 * factory that makes it.
 *
 * @api
 */
final readonly class ServiceDeclaration
{
    /**
     * @param class-string                          $service
     * @param class-string<ServiceFactoryInterface> $factory
     */
    public function __construct(
        public string $service,
        public string $factory,
    ) {}
}
