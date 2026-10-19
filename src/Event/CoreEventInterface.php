<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event;

/**
 * An event only the CMS fires. A host's dispatcher refuses one dispatched by
 * an add-on, so no add-on can forge a save or a sign-in.
 *
 * @api
 */
interface CoreEventInterface extends EventInterface {}
