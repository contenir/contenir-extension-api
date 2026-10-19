<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance;

use Contenir\Extension\Api\Event\Outcome;

/**
 * What a host reports back to the editor after a save: whether it went
 * through, the record's id, and what its Pre listeners said.
 *
 * @api
 */
final readonly class SaveOutcome
{
    /**
     * @param array<string, list<string>> $errors   Field errors, which held the save
     * @param array<string, list<string>> $warnings Notes for the editor
     * @param list<string>                $flags
     *
     * @mago-expect lint:excessive-parameter-list One parameter per thing the editor is told.
     */
    public function __construct(
        public Outcome $outcome,
        public string|int|null $id,
        public string $message = '',
        public array $errors = [],
        public array $warnings = [],
        public array $flags = [],
    ) {}
}
