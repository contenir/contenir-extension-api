<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Override;

/**
 * The probe's NoteServiceInterface.
 *
 * @api
 */
final readonly class NoteService implements NoteServiceInterface
{
    public function __construct(
        private string $madeBy,
        private string $note,
    ) {}

    #[Override]
    public function madeBy(): string
    {
        return $this->madeBy;
    }

    #[Override]
    public function note(): string
    {
        return $this->note;
    }
}
