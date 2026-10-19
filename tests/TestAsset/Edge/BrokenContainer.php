<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Edge;

use Contenir\Extension\Api\Testing\ServiceNotFoundException;
use Override;
use Psr\Container\ContainerInterface;

final class BrokenContainer implements ContainerInterface
{
    /**
     * @throws ServiceNotFoundException Always.
     */
    #[Override]
    public function get(string $id): mixed
    {
        throw ServiceNotFoundException::for($id);
    }

    #[Override]
    public function has(string $id): bool
    {
        return true;
    }
}
