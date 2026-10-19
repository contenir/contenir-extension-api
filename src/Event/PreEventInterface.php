<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * The stage before anything is written. Its listeners run one after another
 * on the same event. Each may stop the action, add field errors, warn or
 * flag; the first to stop it ends the chain. The host goes ahead only when
 * the event still proceeds() after every listener has run.
 *
 * @api
 */
interface PreEventInterface extends EventInterface, StoppableEventInterface
{
    /**
     * An error against one field. The action does not go ahead, but later
     * listeners still run, so every error can be shown at once.
     */
    public function addError(string $field, string $message): void;

    /**
     * Lets the action go ahead but marks its subject, such as an entry filed
     * as spam.
     */
    public function flag(string $reason): void;

    /**
     * Whether the action may go ahead: nothing stopped it and no field has an
     * error.
     */
    public function proceeds(): bool;

    /**
     * Stops the action with a message the form shows, and ends the chain.
     * $detail, such as a failure's technical summary, is shown only to
     * administrators.
     */
    public function stop(string $message, string $detail = ''): void;

    /**
     * A note the editor sees once the action has gone ahead ("save, but show
     * this"); $field is empty for a note about the whole record.
     */
    public function warn(string $field, string $message): void;
}
