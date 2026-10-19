<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event;

/**
 * What the host tells whoever started an action, such as a save, once it is
 * over: whether it went through, the subject's id, and what its Pre
 * listeners said. The message is for everyone; the detail, such as the
 * summary of a failure, is only for administrators.
 *
 * @api
 */
final readonly class ActionResult
{
    /**
     * @param array<string, list<string>> $errors   Field errors, which held the action
     * @param array<string, list<string>> $warnings Notes for the editor
     * @param list<string>                $flags
     *
     * @mago-expect lint:excessive-parameter-list One parameter per thing the editor is told.
     */
    public function __construct(
        public Outcome $outcome,
        public string|int|null $id,
        public string $message = '',
        public string $detail = '',
        public array $errors = [],
        public array $warnings = [],
        public array $flags = [],
    ) {}

    public function succeeded(): bool
    {
        return Outcome::Done === $this->outcome;
    }
}
