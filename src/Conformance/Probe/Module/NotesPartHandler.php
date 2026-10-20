<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe\Module;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Handler\HandlerInterface;
use Contenir\Extension\Api\Handler\HandlerResult;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The probe module's notes list as a fragment, with values that need
 * escaping, answered as Created.
 *
 * @api
 */
final class NotesPartHandler implements HandlerInterface
{
    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): HandlerResult
    {
        return HandlerResult::fragment('notes/list', ['notes' => ['<i>One</i>']], 201);
    }
}
