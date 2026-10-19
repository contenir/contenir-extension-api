<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Url;

/**
 * Finds where a record appears on the public site. The host resolves it
 * through the site's routes, or through a section's URL template in
 * admin.xml for sections the site doesn't route itself.
 *
 * @api
 */
interface UrlResolverInterface
{
    /**
     * The record's absolute public URL, or null when it has none: it is not
     * published, or its section has no public page.
     */
    public function url(RecordReference $record): ?string;
}
