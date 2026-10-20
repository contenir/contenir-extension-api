<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Edge;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Handler\HandlerInterface;
use Contenir\Extension\Api\Handler\HandlerResult;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Answers with a template, though its add-on is not a module.
 */
final class StrayTemplateHandler implements HandlerInterface
{
    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): HandlerResult
    {
        return HandlerResult::template('page', 'Stray');
    }
}
