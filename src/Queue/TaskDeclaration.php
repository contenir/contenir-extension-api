<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Queue;

/**
 * Queued work an add-on registered.
 *
 * @api
 */
final readonly class TaskDeclaration
{
    /**
     * @param class-string<QueuedTaskInterface> $class
     */
    public function __construct(
        public string $name,
        public string $class,
    ) {}
}
