<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Entry;

use Contenir\Extension\Api\Event\AbstractChangePreEvent;
use Contenir\Extension\Api\Event\Change;
use Contenir\Extension\Api\Event\CoreEventInterface;

/**
 * Before a form builder entry is stored. Listeners may change values, add field errors, stop it, or flag it ("accept, but file as spam").
 *
 * @api
 */
final class EntryPreSubmit extends AbstractChangePreEvent implements CoreEventInterface
{
    public function __construct(
        public readonly string $form,
        Change $change,
    ) {
        parent::__construct($change);
    }
}
