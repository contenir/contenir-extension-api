<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Module;

use Contenir\Extension\Api\Context\ExtensionContextInterface;

/**
 * What a module reads on the host's dashboard card for it, beside the links to its sections. The host draws it
 * in the layout it shares with the cards of the parts of the CMS that measure something, so cards of different
 * modules sit alike. Return null while there is nothing to read yet, and the card is drawn without one.
 *
 * @api
 */
interface MeterInterface
{
    public function meter(ExtensionContextInterface $context): ?Meter;
}
