<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Schedule;

/**
 * Recurring work an add-on declared. Declaring one tells the host the add-on
 * needs the job runner.
 *
 * @api
 */
final readonly class ScheduleDeclaration
{
    /**
     * @param class-string<ScheduledTaskInterface> $class
     */
    public function __construct(
        public string $name,
        public string $class,
        public Frequency $frequency,
    ) {}
}
