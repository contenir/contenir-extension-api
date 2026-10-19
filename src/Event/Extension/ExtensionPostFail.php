<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Extension;

use Contenir\Extension\Api\Event\AbstractPostEvent;
use Contenir\Extension\Api\Event\CoreEventInterface;
use Contenir\Extension\Api\Event\Outcome;
use Contenir\Extension\Api\Handle;

/**
 * After an add-on's code throws: a listener, a scheduled task, a handler, a webhook, an operation, a check or a fix.
 * It carries a safe summary, never the exception itself, which could hold secrets. Core counts these for the update check; an alerts add-on could pass them on.
 *
 * @api
 */
final readonly class ExtensionPostFail extends AbstractPostEvent implements CoreEventInterface
{
    /**
     * @param string $name    The listener, task, handler or other piece that failed, by its declared name
     * @param string $summary What went wrong, with nothing secret in it
     */
    public function __construct(
        public Handle $handle,
        public FailurePoint $point,
        public string $name,
        string $summary,
    ) {
        parent::__construct(Outcome::Failed, $summary);
    }
}
