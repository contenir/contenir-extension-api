<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Handler\HandlerInterface;
use Contenir\Extension\Api\Handler\HandlerResult;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * An admin handler that throws.
 *
 * @api
 */
final class BrokenHandler implements HandlerInterface
{
    /**
     * @throws RuntimeException Always.
     */
    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): HandlerResult
    {
        throw new RuntimeException('The probe handler failed on purpose');
    }
}
