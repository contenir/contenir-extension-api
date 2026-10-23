<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Record;

/**
 * Where a record stands, as the editor sees it. Only a published record
 * appears on the public site.
 *
 * @api
 */
enum RecordStatus: string
{
    /**
     * On the public site.
     */
    case Published = 'published';

    /**
     * Not yet published.
     */
    case Draft = 'draft';

    /**
     * Taken off the public site.
     */
    case Unpublished = 'unpublished';

    /**
     * Kept, but out of use.
     */
    case Archived = 'archived';

    public function isLive(): bool
    {
        return self::Published === $this;
    }
}
