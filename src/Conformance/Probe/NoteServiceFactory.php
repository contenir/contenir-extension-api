<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Service\ServiceFactoryInterface;
use Override;

use function is_string;

/**
 * Makes the NoteService from the add-on's context: its handle, and the note
 * in its settings, so a test sees the factory was given that add-on's own.
 *
 * @api
 */
final class NoteServiceFactory implements ServiceFactoryInterface
{
    #[Override]
    public function create(ExtensionContextInterface $context): NoteService
    {
        /** @var mixed $note */
        $note = $context->settings()->get('note');

        return new NoteService($context->handle()->value, is_string($note) ? $note : '');
    }
}
