<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Section;

use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Internal\Names;

/**
 * A listener a section block binds to its section, as if the site had
 * written the <listen> line itself. It names one of the add-on's declared
 * listeners and one event it hears, and may narrow the listener's scope to
 * some fields, as a line can. A <listen> line the site writes for the same
 * listener and event in that section replaces it.
 *
 * @api
 */
final readonly class ImpliedListener
{
    /**
     * @param class-string<EventInterface> $event
     * @param list<string>                 $fields Narrows the listener's scope; none keeps it as declared
     *
     * @throws \Contenir\Extension\Api\Exception\InvalidDeclarationException When the name or a field is malformed, or the class is not an event.
     */
    public function __construct(
        public string $listener,
        public string $event,
        public array $fields = [],
    ) {
        Names::name('Listener', $listener);
        Names::implementing('Event', $event, EventInterface::class);
        foreach ($fields as $field) {
            Names::field($field);
        }
    }
}
