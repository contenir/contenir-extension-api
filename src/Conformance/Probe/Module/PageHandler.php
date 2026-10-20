<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe\Module;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Handler\HandlerInterface;
use Contenir\Extension\Api\Handler\HandlerResult;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The module's home, drawn from its page template with values that need
 * escaping.
 *
 * @api
 */
final class PageHandler implements HandlerInterface
{
    public const string NAME = '<b>Ada</b>';

    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): HandlerResult
    {
        return HandlerResult::template('page', 'Probes', ['name' => self::NAME, 'notes' => ['First', 'Fish & chips']]);
    }
}
