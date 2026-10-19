<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Schedule;

use Contenir\Extension\Api\Context\ExtensionContextInterface;

/**
 * Recurring work an add-on declares with Registry::schedule(). The host's
 * job runner calls it when it is due; a run that is still going when the
 * next falls due is skipped. Throwing marks the run failed.
 *
 * @api
 */
interface ScheduledTaskInterface
{
    public function run(ExtensionContextInterface $context): void;
}
