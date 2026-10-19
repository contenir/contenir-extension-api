<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Event\Entry;

use Contenir\Extension\Api\Event\AbstractPostEvent;
use Contenir\Extension\Api\Event\CoreEventInterface;

/**
 * After expired entries' personal data is redacted under the form's retention rule.
 *
 * @api
 */
final readonly class EntryPostRedact extends AbstractPostEvent implements CoreEventInterface
{
    /**
     * @param list<string|int> $ids The entries redacted
     */
    public function __construct(
        public string $form,
        public array $ids,
    ) {
        parent::__construct();
    }
}
