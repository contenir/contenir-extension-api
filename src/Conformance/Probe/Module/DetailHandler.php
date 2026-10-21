<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe\Module;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Handler\HandlerInterface;
use Contenir\Extension\Api\Handler\HandlerResult;
use Contenir\Extension\Api\Handler\ParentPage;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A page under another: the probe page drawn again, as one of the notes
 * page's, which the breadcrumb shows it under.
 *
 * @api
 */
final class DetailHandler implements HandlerInterface
{
    public const string PARENT = 'Fish & chips';

    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): HandlerResult
    {
        return HandlerResult::template(
            'page',
            'Probe detail',
            ['name' => PageHandler::NAME, 'notes' => []],
            parent: new ParentPage(self::PARENT, 'notes', ['page' => 3]),
        );
    }
}
