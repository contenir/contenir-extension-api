<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe\Module;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Handler\HandlerInterface;
use Contenir\Extension\Api\Handler\HandlerResult;
use Contenir\Extension\Api\Handler\Tab;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The module's home, drawn from its page template with values that need
 * escaping, and two tabs the template prints under its greeting.
 *
 * @api
 */
final class PageHandler implements HandlerInterface
{
    public const string NAME = '<b>Ada</b>';

    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): HandlerResult
    {
        return HandlerResult::template(
            'page',
            'Probes',
            ['name' => self::NAME, 'notes' => ['First', 'Fish & chips']],
            [
                new Tab('Probes', 'page', current: true),
                new Tab('Notes', 'notes', ['page' => 3]),
            ],
        );
    }
}
