<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Script;

use Contenir\Extension\Api\Context\ExtensionContextInterface;

/**
 * Supplies an add-on's scripts for public pages, usually from its settings
 * (a tracking id, an account key). A site adapter renders every add-on's
 * scripts in one place, so consent can gate them all.
 *
 * @api
 */
interface ScriptProviderInterface
{
    /**
     * @return list<Script>
     */
    public function scripts(ExtensionContextInterface $context): array;
}
