<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Handler\HandlerInterface;
use Contenir\Extension\Api\Handler\HandlerResult;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * An admin handler answering with JSON.
 *
 * @api
 */
final class StatusHandler implements HandlerInterface
{
    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): HandlerResult
    {
        Journal::from($context)->write('status');

        return HandlerResult::json(['ok' => true]);
    }
}
