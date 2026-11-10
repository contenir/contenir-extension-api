<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Closure;
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Event\Extension\FailurePoint;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Registration;
use Contenir\Extension\Api\Section\Element;
use Contenir\Extension\Api\Section\SectionsInterface;

/**
 * The in-memory host's section blocks: what each section's <extensions>
 * holds, each add-on's view of its own blocks, and the listeners they imply.
 *
 * @internal
 */
final readonly class HostSections
{
    /**
     * @param Closure(string, string, string, list<string>): array{class-string<EventInterface>, BoundListener} $resolve Finds the listener a line names
     *
     * @mago-expect lint:excessive-parameter-list One parameter per part of the host a block reaches.
     */
    public function __construct(
        private SectionBlocks $blocks,
        private Records $records,
        private HostRecords $hostRecords,
        private Runtime $runtime,
        private Bindings $bindings,
        private Closure $resolve,
        private BlockOverrides $overrides = new BlockOverrides(),
    ) {}

    /**
     * The blocks a loaded add-on reads, or none when it reads no blocks.
     */
    public function for(?Registration $registration): SectionsInterface
    {
        $reader = $registration?->sectionBlock;
        if (null === $registration || null === $reader) {
            return new NoSections();
        }

        return new AddOnSections(
            $registration->handle,
            $this->blocks,
            $this->records,
            (new GuardedBlockReader($this->runtime, $registration->handle, $reader))->columns(...),
            $this->hostRecords->isLinkable(...),
            $this->overrides,
        );
    }

    /**
     * Puts $element inside $section's <extensions>, and binds the listeners
     * it implies when $registration, the add-on it is named after, is loaded.
     * A listener it can't bind is reported as the host reports a failure.
     *
     * @param list<string> $fields
     */
    public function put(string $section, Element $element, array $fields, ?Registration $registration): void
    {
        $this->blocks->put($section, $element, $fields);

        $reader = $registration?->sectionBlock;
        $block  = $this->for($registration)->block($section);
        if (null === $registration || null === $reader || null === $block) {
            return;
        }

        $listeners = (new GuardedBlockReader($this->runtime, $registration->handle, $reader))->listeners($block);
        foreach ($listeners as $implied) {
            try {
                [$event, $bound] = ($this->resolve)(
                    $registration->handle->value,
                    $implied->listener,
                    $implied->event,
                    $implied->fields,
                );
                $this->bindings->imply($section, $event, $bound);
            } catch (InvalidDeclarationException $failure) {
                $this->runtime->report($registration->handle, FailurePoint::Section, $implied->listener, $failure);
            }
        }
    }
}
