<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Entry;

use Contenir\Extension\Api\Event\AbstractPostEvent;
use Contenir\Extension\Api\Event\Change;
use Contenir\Extension\Api\Event\ChangeEventInterface;
use Contenir\Extension\Api\Event\CoreEventInterface;
use Contenir\Extension\Api\Event\Outcome;
use Override;

/**
 * After an entry's status changes, whatever happened.
 *
 * @api
 */
final readonly class EntryPostStatusChange extends AbstractPostEvent implements ChangeEventInterface, CoreEventInterface
{
    public function __construct(
        public string $form,
        public string|int $id,
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
