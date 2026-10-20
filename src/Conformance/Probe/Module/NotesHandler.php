<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe\Module;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Handler\HandlerInterface;
use Contenir\Extension\Api\Handler\HandlerResult;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A module handler answering with JSON.
 *
 * @api
 */
final class NotesHandler implements HandlerInterface
{
    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): HandlerResult
    {
        return HandlerResult::json(['notes' => 2]);
    }
}
