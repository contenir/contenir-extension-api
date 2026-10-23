<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Record;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;
use Contenir\Extension\Api\Url\RecordReference;

/**
 * A record an add-on asks the host to create as a draft: its section, its
 * title, its path on the site, such as /about/our-team, and optionally the
 * workflow it is made with and the record it sits under, whose defaults the
 * host copies as adding a subpage does.
 *
 * @api
 */
final readonly class NewRecord
{
    /**
     * @throws InvalidDeclarationException When the section, title or path is empty.
     */
    public function __construct(
        public string $section,
        public string $title,
        public string $path,
        public ?string $workflow = null,
        public ?RecordReference $parent = null,
    ) {
        Names::text('A new record section', $section);
        Names::text('A new record title', $title);
        Names::text('A new record path', $path);
    }
}
