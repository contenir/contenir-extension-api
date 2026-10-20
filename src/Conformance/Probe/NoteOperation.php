<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Operation\OperationContextInterface;
use Contenir\Extension\Api\Operation\OperationHandlerInterface;
use Contenir\Extension\Api\Operation\OperationResult;
use Contenir\Extension\Api\Operation\OperationResultInterface;
use Override;

use function is_string;
use function sprintf;

/**
 * An operation on the probe's settings section. It logs that it ran, and
 * answers with its add-on's handle, the note in its settings, the "value"
 * posted and the section's resource.
 *
 * @api
 */
final class NoteOperation implements OperationHandlerInterface
{
    #[Override]
    public function handle(OperationContextInterface $context): OperationResultInterface
    {
        $extension = $context->extension();
        if (null === $extension) {
            return OperationResult::message('danger', 'No add-on context');
        }

        $context->logger()->info('The note operation ran');

        /** @var mixed $note */
        $note = $extension->settings()->get('note');
        /** @var mixed $value */
        $value = $context->post('value');

        return OperationResult::message('success', sprintf(
            '%s: %s, posted %s, on %s',
            $extension->handle()->value,
            is_string($note) ? $note : '',
            is_string($value) ? $value : 'nothing',
            $context->section()->resourceId(),
        ));
    }
}
