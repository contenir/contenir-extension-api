<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Boundary;

use Contenir\Extension\Api\Exception\ExceptionInterface;
use RuntimeException;

use function implode;

/**
 * An add-on's composer.json puts it outside the boundary: it is not an add-on,
 * its classes are outside Contenir\Extension\, or it requires something an
 * add-on may not use.
 *
 * @api
 */
final class BoundaryException extends RuntimeException implements ExceptionInterface
{
    /**
     * @param non-empty-list<string> $problems
     */
    public function __construct(
        public readonly array $problems,
    ) {
        parent::__construct(implode("\n", $problems));
    }
}
