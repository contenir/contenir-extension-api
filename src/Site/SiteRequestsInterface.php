<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Site;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Url\RecordReference;

/**
 * Asks the site about a saved record. The host signs the record's values as
 * it signs a preview, with the keys it shares with the site, and posts them
 * to one of the site's own /api/ endpoints, which opens them as a preview
 * does. An add-on names the endpoint and reads its answer, and never sees the
 * keys, the token or the values sent.
 *
 * @api
 */
interface SiteRequestsInterface
{
    /**
     * The site's answer, whatever its status, or null when the site can't be
     * asked: it shares no keys with the host, the record can't be found or
     * signed, or the site didn't answer in time, which the host logs. An add-on
     * falls back on what it can read itself.
     *
     * @param string $path One of the site's /api/ paths, as SitePath checks it
     * @param array<string, string> $fields Posted beside the token, for the endpoint to read
     *
     * @throws InvalidDeclarationException When the path isn't one of the site's /api/ paths.
     */
    public function post(RecordReference $record, string $path, array $fields = []): ?SiteAnswer;
}
