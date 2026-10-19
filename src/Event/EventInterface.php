<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event;

/**
 * Anything a listener can listen to: the CMS's own events and the events an
 * add-on fires under its own Event\ namespace.
 *
 * Events are named <Type>Pre<Action>, <Type><Action> and <Type>Post<Action>,
 * one guarantee per stage. A Pre event runs before anything is written; the
 * main event runs inside the action's transaction; a Post event runs after
 * the transaction ends, whatever happened.
 *
 * @api
 */
interface EventInterface {}
