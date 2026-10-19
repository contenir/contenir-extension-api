<?php

declare(strict_types=1);

namespace Contenir\Extension\Api;

/**
 * Implemented by extensions that ship their own SVG icons. The host registers
 * the returned directory with the Icon view helper at boot, so the extension's
 * icons resolve by name like any host icon. Return null to ship none.
 *
 * @api
 */
interface IconPathProviderInterface
{
    public function getIconPath(): ?string;
}
