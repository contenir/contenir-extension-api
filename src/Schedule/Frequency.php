<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Schedule;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;

use function count;
use function preg_match;
use function preg_split;
use function sprintf;
use function trim;

/**
 * How often a scheduled task runs: every so many minutes, or on a cron
 * expression. The job runner wakes once a minute, so a minute is the finest
 * interval.
 *
 * @api
 */
final readonly class Frequency
{
    private const string CRON_FIELD = '/^[0-9A-Za-z*,\/-]+$/D';

    private function __construct(
        public ?int $minutes,
        public ?string $cron,
    ) {}

    /**
     * @param string $expression Five fields: minute, hour, day of month, month, day of week
     *
     * @throws InvalidDeclarationException When $expression is not five cron fields.
     */
    public static function cron(string $expression): self
    {
        $fields = preg_split('/\s+/', trim($expression));
        if (false === $fields || 5 !== count($fields)) {
            throw InvalidDeclarationException::because(sprintf(
                'Cron expression "%s" must have five fields',
                $expression,
            ));
        }

        foreach ($fields as $field) {
            if (1 !== preg_match(self::CRON_FIELD, $field)) {
                throw InvalidDeclarationException::because(sprintf(
                    'Cron expression "%s" has an invalid field "%s"',
                    $expression,
                    $field,
                ));
            }
        }

        return new self(null, trim($expression));
    }

    /**
     * @throws InvalidDeclarationException When $minutes is less than one.
     */
    public static function everyMinutes(int $minutes): self
    {
        if ($minutes < 1) {
            throw InvalidDeclarationException::because(sprintf(
                'A schedule must run at most once a minute, not every %d',
                $minutes,
            ));
        }

        return new self($minutes, null);
    }
}
