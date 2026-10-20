<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe\Module;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Module\MenuGroup;
use Contenir\Extension\Api\Module\MenuInterface;
use Contenir\Extension\Api\Module\MenuItem;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Two notes in the section menu, the one the request asks for current.
 *
 * @api
 */
final class ProbeMenu implements MenuInterface
{
    private static function asked(ServerRequestInterface $request, string $note): bool
    {
        return $note === ($request->getQueryParams()['note'] ?? null);
    }

    #[Override]
    public function groups(ServerRequestInterface $request, ExtensionContextInterface $context): array
    {
        return [new MenuGroup(null, [
            new MenuItem('First note', 'notes', ['note' => 1], self::asked($request, '1')),
            new MenuItem('Second note', 'notes', ['note' => 2], self::asked($request, '2')),
        ])];
    }
}
