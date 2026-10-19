<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Listener;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Event\EventInterface;

/**
 * Reacts to an event, once a site has bound it to a section. A listener at
 * Pre may change values, add errors, warn, flag or stop; a main-stage
 * listener may write its own rows inside the transaction; a Post listener
 * does anything outside the database.
 *
 * Throwing at Pre or the main stage stops the action and says why on the
 * form. Throwing at Post is logged and reported as ExtensionPostFail.
 *
 * @api
 */
interface ListenerInterface
{
    public function handle(EventInterface $event, ExtensionContextInterface $context): void;
}
