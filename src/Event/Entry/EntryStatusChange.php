<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Entry;

use Contenir\Extension\Api\Event\Change;
use Contenir\Extension\Api\Event\ChangeEventInterface;
use Contenir\Extension\Api\Event\CoreEventInterface;
use Override;

/**
 * Inside the transaction when an entry's status changes.
 *
 * @api
 */
final readonly class EntryStatusChange implements ChangeEventInterface, CoreEventInterface
{
    public function __construct(
        public string $form,
        public string|int $id,
        private Change $change,
    ) {}

    #[Override]
    public function change(): Change
    {
        return $this->change;
    }
}
