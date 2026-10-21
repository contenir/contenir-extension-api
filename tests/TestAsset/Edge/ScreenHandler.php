<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Edge;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Handler\HandlerInterface;
use Contenir\Extension\Api\Handler\HandlerResult;
use Contenir\Extension\Api\Handler\ParentPage;
use Contenir\Extension\Api\Handler\Screen;
use Override;
use Psr\Http\Message\ServerRequestInterface;

final class ScreenHandler implements HandlerInterface
{
    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): HandlerResult
    {
        return HandlerResult::screen(
            'parent' === ($request->getQueryParams()['under'] ?? null)
                ? new Screen('Subscribers', parent: new ParentPage('Newsletter', 'screen'))
                : new Screen('Subscribers'),
        );
    }
}
