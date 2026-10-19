<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Event\AbstractPreEvent;
use Contenir\Extension\Api\Event\ActionResult;
use Contenir\Extension\Api\Event\Outcome;

/**
 * What the Pre events of one action said, gathered across them all for the
 * result the editor sees.
 *
 * @internal
 */
final class PreNotes
{
    /**
     * @var array<string, list<string>>
     */
    private array $errors = [];

    /**
     * @var array<string, list<string>>
     */
    private array $warnings = [];

    /**
     * @var list<string>
     */
    private array $flags = [];

    /**
     * @param array<string, list<string>> $into
     * @param array<string, list<string>> $more
     *
     * @return array<string, list<string>>
     */
    private static function merge(array $into, array $more): array
    {
        foreach ($more as $field => $messages) {
            foreach ($messages as $message) {
                $into[$field][] = $message;
            }
        }

        return $into;
    }

    public function add(AbstractPreEvent $event): void
    {
        $this->errors   = self::merge($this->errors, $event->errors());
        $this->warnings = self::merge($this->warnings, $event->warnings());
        $this->flags    = [...$this->flags, ...$event->flags()];
    }

    public function result(Outcome $outcome, string|int|null $id, string $message, string $detail): ActionResult
    {
        return new ActionResult($outcome, $id, $message, $detail, $this->errors, $this->warnings, $this->flags);
    }
}
