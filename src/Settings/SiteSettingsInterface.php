<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Settings;

/**
 * The site's own settings, lent to add-ons read-only: every one of them, by
 * a dot-separated key into the site's settings, such as contact.telephone.
 * A host that has site settings offers them from each add-on's services(),
 * named by this interface; not every host does, so an add-on asks has()
 * first. An add-on's own settings are its context's settings().
 *
 * @api
 */
interface SiteSettingsInterface
{
    /**
     * The setting at $key as text, or null when it isn't set or holds more
     * than one value.
     */
    public function get(string $key): ?string;
}
