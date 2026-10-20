<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Panel;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;
use Contenir\Extension\Api\Url\RecordReference;

/**
 * The record a panel is shown beside: its section (as record events name
 * it), its id and its values as stored.
 *
 * @api
 */
final readonly class RecordView
{
    /**
     * @param array<string, mixed> $values By field
     *
     * @throws InvalidDeclarationException When the section is empty.
     */
    public function __construct(
        public string $section,
        public string|int $id,
        public array $values = [],
    ) {
        Names::text('A record view section', $section);
    }

    /**
     * The record by its section and id, to store or resolve to a public URL.
     */
    public function reference(): RecordReference
    {
        return new RecordReference($this->section, $this->id);
    }
}
