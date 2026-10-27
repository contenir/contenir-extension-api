<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event;

/**
 * An add-on's own event, fired once it has changed what the public site shows, such as
 * publishing a menu. The CMS reacts to it as to a record save: it clears its caches, purges the
 * CDN's copy of the site and sends the site's webhook, once for the whole action, when the change
 * succeeded.
 *
 * The event lives under the add-on's own Event\ namespace like any other it fires; a site can
 * narrow the CMS's reactions to it by its class name in admin.xml.
 *
 * @api
 */
interface SiteChangeEventInterface extends PostEventInterface {}
