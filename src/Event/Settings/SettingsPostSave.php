<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Settings;

use Contenir\Extension\Api\Event\AbstractPostEvent;
use Contenir\Extension\Api\Event\Change;
use Contenir\Extension\Api\Event\ChangeEventInterface;
use Contenir\Extension\Api\Event\CoreEventInterface;
use Contenir\Extension\Api\Event\Outcome;
use Override;

/**
 * After a settings section is saved, whatever happened.
 *
 * @api
 */
final readonly class SettingsPostSave extends AbstractPostEvent implements ChangeEventInterface, CoreEventInterface
{
    public function __construct(
        public string $section,
        private Change $change,
        Outcome $outcome = Outcome::Done,
        string $reason = '',
    ) {
        parent::__construct($outcome, $reason);
    }

    #[Override]
    public function change(): Change
    {
        return $this->change;
    }
}
