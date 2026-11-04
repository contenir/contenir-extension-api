<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Event\Extension\FailurePoint;
use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Section\SectionBlock;
use Contenir\Extension\Api\Section\SectionBlockInterface;
use Override;
use Throwable;

use function sprintf;

/**
 * An add-on's section block reader, made when it is asked, that answers
 * nothing when it can't be made or fails, and reports the failure as the
 * host reports any add-on's.
 *
 * @internal
 */
final readonly class GuardedBlockReader implements SectionBlockInterface
{
    /**
     * @param class-string<SectionBlockInterface> $reader
     */
    public function __construct(
        private Runtime $runtime,
        private Handle $handle,
        private string $reader,
    ) {}

    #[Override]
    public function columns(SectionBlock $block): array
    {
        try {
            return $this->reader()->columns($block);
        } catch (Throwable $failure) {
            $this->report($block, 'columns', $failure);

            return [];
        }
    }

    #[Override]
    public function listeners(SectionBlock $block): array
    {
        try {
            return $this->reader()->listeners($block);
        } catch (Throwable $failure) {
            $this->report($block, 'listeners', $failure);

            return [];
        }
    }

    private function reader(): SectionBlockInterface
    {
        return $this->runtime->make($this->reader, SectionBlockInterface::class);
    }

    private function report(SectionBlock $block, string $what, Throwable $failure): void
    {
        $this->runtime->report(
            $this->handle,
            FailurePoint::Section,
            sprintf('%s %s', $block->section, $what),
            $failure,
        );
    }
}
