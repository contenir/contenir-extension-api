<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing;

use Contenir\Extension\Api\Exception\ExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

use function sprintf;

/**
 * @api
 */
final class ServiceNotFoundException extends RuntimeException implements ExceptionInterface, NotFoundExceptionInterface
{
    public static function for(string $id): self
    {
        return new self(sprintf('No service "%s"', $id));
    }
}
