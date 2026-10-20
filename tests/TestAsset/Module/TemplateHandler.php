<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Module;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Handler\HandlerInterface;
use Contenir\Extension\Api\Handler\HandlerResult;
use Override;
use Psr\Http\Message\ServerRequestInterface;

use function basename;

/**
 * Answers with the template named by the request's last path segment.
 */
final class TemplateHandler implements HandlerInterface
{
    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): HandlerResult
    {
        return HandlerResult::template(basename($request->getUri()->getPath()), 'Fixture', ['note' => 'Tea & cake']);
    }
}
