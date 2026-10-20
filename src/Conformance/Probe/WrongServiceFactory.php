<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Service\ServiceFactoryInterface;
use Override;

/**
 * A factory that makes a NoteService where a WrongServiceInterface was
 * declared.
 *
 * @api
 */
final class WrongServiceFactory implements ServiceFactoryInterface
{
    #[Override]
    public function create(ExtensionContextInterface $context): object
    {
        return new NoteService($context->handle()->value, 'wrong');
    }
}
