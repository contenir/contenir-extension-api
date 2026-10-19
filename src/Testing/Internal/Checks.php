<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Diagnostic\CheckInterface;
use Contenir\Extension\Api\Diagnostic\CheckResult;
use Contenir\Extension\Api\Diagnostic\FixInterface;
use Contenir\Extension\Api\Diagnostic\OneClickFix;
use Contenir\Extension\Api\Event\Extension\FailurePoint;
use Contenir\Extension\Api\Registration;
use LogicException;
use Throwable;

use function sprintf;

/**
 * Runs add-ons' checks, as the update check does, and applies their fixes.
 *
 * @internal
 */
final readonly class Checks
{
    public function __construct(
        private Runtime $runtime,
    ) {}

    /**
     * @throws LogicException When the add-on has no such check.
     */
    public function check(Registration $registration, string $name): CheckResult
    {
        $declaration = $registration->diagnostics[$name] ?? throw new LogicException(
            sprintf('Add-on "%s" has no check "%s"', $registration->handle->value, $name),
        );

        try {
            return $this->runtime
                ->make($declaration->class, CheckInterface::class)
                ->check($this->runtime->context($registration->handle));
        } catch (Throwable $failure) {
            return CheckResult::failure(
                $this->runtime->report($registration->handle, FailurePoint::Check, $name, $failure),
            );
        }
    }

    /**
     * Applies the fix the check offers now, then runs the check again.
     *
     * @param array<string, mixed> $values
     *
     * @throws LogicException When the add-on has no such check.
     */
    public function fix(Registration $registration, string $name, array $values): CheckResult
    {
        $fix     = $this->check($registration, $name)->fix;
        $handler = $fix?->handler;
        if (null !== $handler) {
            try {
                $this->runtime
                    ->make($handler, FixInterface::class)
                    ->apply($fix instanceof OneClickFix ? [] : $values, $this->runtime->context($registration->handle));
            } catch (Throwable $failure) {
                $this->runtime->report($registration->handle, FailurePoint::Fix, $name, $failure);
            }
        }

        return $this->check($registration, $name);
    }
}
