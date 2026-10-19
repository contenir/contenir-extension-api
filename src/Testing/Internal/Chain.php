<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Exception\DispatchRefusedException;

use function array_pop;
use function count;
use function in_array;

/**
 * The events an add-on has fired within one chain, which caps how deep the
 * chain goes and stops an event recurring in it.
 *
 * @internal
 */
final class Chain
{
    public const int MAX_DEPTH = 8;

    /**
     * @var list<string>
     */
    private array $running = [];

    /**
     * @throws DispatchRefusedException When $event is already running or the chain is full.
     */
    public function enter(string $event): void
    {
        if (in_array($event, $this->running, strict: true)) {
            throw DispatchRefusedException::recurrence($event);
        }

        $depth = count($this->running) + 1;
        if ($depth > self::MAX_DEPTH) {
            throw DispatchRefusedException::tooDeep($event, $depth);
        }

        $this->running[] = $event;
    }

    public function leave(): void
    {
        array_pop($this->running);
    }
}
