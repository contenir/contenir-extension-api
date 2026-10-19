<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\ExtensionInterface;
use Contenir\Extension\Api\Registry;
use Override;

/**
 * An add-on whose declaration the registry refuses: a handler may not be
 * named "fix".
 *
 * @api
 */
final class InvalidExtension implements ExtensionInterface
{
    #[Override]
    public function register(Registry $registry): void
    {
        $registry->handler(Registry::FIX, StatusHandler::class);
    }
}
