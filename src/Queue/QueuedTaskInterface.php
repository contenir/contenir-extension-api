<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Queue;

use Contenir\Extension\Api\Context\ExtensionContextInterface;

/**
 * Work an add-on declares with Registry::task() and queues with TaskQueueInterface, such as a bulk change too
 * long for the request that asked for it. The host's job runner calls it with the payload it was queued with,
 * as the editor who queued it: the services it lends, such as the record updates, check that editor's
 * permissions again at the time it runs. Throwing marks the run failed, and the host tries it again.
 *
 * @api
 */
interface QueuedTaskInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function run(array $payload, ExtensionContextInterface $context): void;
}
