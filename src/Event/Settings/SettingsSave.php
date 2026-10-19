<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Settings;

use Contenir\Extension\Api\Event\Change;
use Contenir\Extension\Api\Event\ChangeEventInterface;
use Contenir\Extension\Api\Event\CoreEventInterface;
use Override;

/**
 * Inside the transaction when a settings section is saved.
 *
 * @api
 */
final readonly class SettingsSave implements ChangeEventInterface, CoreEventInterface
{
    public function __construct(
        public string $section,
        private Change $change,
    ) {}

    #[Override]
    public function change(): Change
    {
        return $this->change;
    }
}
