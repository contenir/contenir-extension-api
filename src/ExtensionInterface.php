<?php

declare(strict_types=1);

namespace Contenir\Extension\Api;

/**
 * An add-on's entry class, named by extra.contenir.extension in its
 * composer.json. The host constructs it with no arguments and calls
 * register() once, with a registry for the add-on's handle. Everything the
 * add-on adds to the CMS goes through that registry.
 *
 * A host that catches an exception from register() skips the add-on and
 * reports why in its update check; nothing the add-on declared is used.
 *
 * @api
 */
interface ExtensionInterface
{
    public function register(Registry $registry): void;
}
