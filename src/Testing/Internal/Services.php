<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Testing\ServiceMap;
use Contenir\Extension\Api\Testing\ServiceNotFoundException;
use Override;
use Psr\Container\ContainerInterface;

/**
 * The host's services, which booting adds to.
 *
 * @internal
 */
final class Services implements ContainerInterface
{
    public function __construct(
        private ServiceMap $map,
    ) {}

    /**
     * @param array<string, object> $services
     */
    public function add(array $services): void
    {
        $this->map = $this->map->with($services);
    }

    /**
     * @throws ServiceNotFoundException
     */
    #[Override]
    public function get(string $id): mixed
    {
        return $this->map->get($id);
    }

    #[Override]
    public function has(string $id): bool
    {
        return $this->map->has($id);
    }
}
