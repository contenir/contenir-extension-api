<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Module;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The part of a module's section menu that its admin.xml can't list, such as
 * the forms in the form builder's tables. The host draws these groups after
 * the area's categories, on every screen in the area. A module that only adds
 * sections to an existing area, such as Site Pages, has its groups drawn in
 * their place instead, while they list any item it may view; with none, the
 * sections its admin.xml adds are drawn as before.
 *
 * @api
 */
interface MenuInterface
{
    /**
     * $request is the screen being drawn, so an item can say it is current.
     *
     * @return list<MenuGroup>
     */
    public function groups(ServerRequestInterface $request, ExtensionContextInterface $context): array;
}
