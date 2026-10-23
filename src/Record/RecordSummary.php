<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Record;

use Contenir\Extension\Api\Url\RecordReference;

/**
 * What an editor needs to recognise a record: its title, its path on the
 * site, where it stands, and where it is edited in the CMS. The path is
 * relative to the site, such as /about/our-team, or null when the record's
 * section has no pages of its own; it is shown to the editor, while the site
 * resolves the record's real URL with its own routes.
 *
 * @api
 */
final readonly class RecordSummary
{
    public function __construct(
        public RecordReference $record,
        public string $title,
        public RecordStatus $status,
        public ?string $path = null,
        public ?string $editUrl = null,
    ) {}
}
