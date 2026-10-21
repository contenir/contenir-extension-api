<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Event\Extension\FailurePoint;
use Contenir\Extension\Api\Operation\OperationHandlerInterface;
use Contenir\Extension\Api\Operation\OperationResult;
use Contenir\Extension\Api\Operation\OperationResultInterface;
use Contenir\Extension\Api\Registration;
use LogicException;
use Throwable;

use function in_array;
use function sprintf;

/**
 * Runs the operations on add-ons' settings sections, as their buttons do,
 * for someone holding the operation's privilege on the section's resource.
 * One that throws is reported, and answers with a plain message.
 *
 * @internal
 */
final readonly class Operations
{
    public const string FAILED = 'That could not be done. The log has the details.';

    public function __construct(
        private Runtime $runtime,
    ) {}

    /**
     * @param array<string, mixed> $posted
     * @param list<string>         $grants
     *
     * @throws LogicException When the add-on has no such section or operation.
     */
    public function run(
        Registration $registration,
        string $section,
        string $name,
        array $posted,
        array $grants,
    ): ?OperationResultInterface {
        $declared = $registration->settingsSections[$section] ?? throw new LogicException(
            sprintf('Add-on "%s" has no settings section "%s"', $registration->handle->value, $section),
        );
        $operation = null;
        foreach ($declared->operations as $candidate) {
            $operation = $candidate->name === $name ? $candidate : $operation;
        }

        if (null === $operation) {
            throw new LogicException(sprintf('Settings section "%s" has no operation "%s"', $section, $name));
        }

        $view     = new SectionView($declared);
        $resource = $view->resourceId();
        if (
            ! in_array($resource, $grants, strict: true)
            && ! in_array("{$resource}:{$operation->privilege}", $grants, strict: true)
        ) {
            return null;
        }

        try {
            return $this->runtime
                ->make($operation->handler, OperationHandlerInterface::class)
                ->handle(new OperationContext($posted, $view, $this->runtime->context($registration->handle)));
        } catch (Throwable $failure) {
            $this->runtime->report($registration->handle, FailurePoint::Operation, $name, $failure);

            return OperationResult::message('danger', self::FAILED);
        }
    }
}
