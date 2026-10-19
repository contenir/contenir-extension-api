<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Settings;

use Contenir\Extension\Api\Event\AbstractChangePreEvent;
use Contenir\Extension\Api\Event\Change;
use Contenir\Extension\Api\Event\CoreEventInterface;

/**
 * Before a settings section is saved. Listeners may change the values, add field errors, warn, flag or stop it.
 *
 * @api
 */
final class SettingsPreSave extends AbstractChangePreEvent implements CoreEventInterface
{
    public function __construct(
        public readonly string $section,
        Change $change,
    ) {
        parent::__construct($change);
    }
}
