<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event;

/**
 * The stage after the action's transaction has ended. It runs whatever
 * happened, so cleanup can undo setup done at Pre; a listener that should act
 * only on success checks succeeded().
 *
 * @api
 */
interface PostEventInterface extends EventInterface
{
    public function outcome(): Outcome;

    /**
     * Why the action did not go through: the stop message, or a safe summary
     * of the failure. Empty when it succeeded.
     */
    public function reason(): string;

    public function succeeded(): bool;
}
