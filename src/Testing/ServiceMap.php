<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing;

use Override;
use Psr\Container\ContainerInterface;

use function array_key_exists;

/**
 * A PSR-11 container over a fixed map of services, for tests.
 *
 * @api
 */
final readonly class ServiceMap implements ContainerInterface
{
    /**
     * @param array<string, mixed> $services By id
     */
    public function __construct(
        private array $services = [],
    ) {}

    /**
     * @throws ServiceNotFoundException When there is no service $id.
     */
    #[Override]
    public function get(string $id): mixed
    {
        if (! array_key_exists($id, $this->services)) {
            throw ServiceNotFoundException::for($id);
        }

        return $this->services[$id];
    }

    #[Override]
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services);
    }

    /**
     * The same services with $more added, replacing any with the same id.
     *
     * @param array<string, mixed> $more
     */
    public function with(array $more): self
    {
        return new self([...$this->services, ...$more]);
    }
}
