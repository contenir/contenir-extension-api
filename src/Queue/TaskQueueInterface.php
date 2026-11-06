<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Queue;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;

/**
 * Queues an add-on's own tasks to run in the host's job runner, lent from services() to add-ons that do work
 * too long for one request, such as SEO's bulk descriptions. A host that has a job runner offers it, named by
 * this interface; not every host does, so an add-on asks has() first and offers no such work without it.
 *
 * A task runs as the editor signed in when it was queued, not as someone allowed everything.
 *
 * @api
 */
interface TaskQueueInterface
{
    /**
     * Queues the add-on's task $task with $payload, which must be JSON-encodable.
     *
     * @param array<string, mixed> $payload
     *
     * @throws InvalidDeclarationException When the add-on declared no such task, or the payload can't be encoded.
     */
    public function enqueue(string $handle, string $task, array $payload = []): void;
}
