<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Listener;

use Contenir\Extension\Api\Event\ChangeEventInterface;
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Event\PostEventInterface;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

use function is_a;
use function sprintf;

/**
 * A listener an add-on declared: what it can do. Where it runs is the site's
 * choice, made by binding it to sections.
 *
 * @api
 */
final readonly class ListenerDeclaration
{
    /**
     * @param class-string<EventInterface>    $event
     * @param class-string<ListenerInterface> $listener
     */
    private function __construct(
        public string $name,
        public string $event,
        public string $listener,
        public Scope $scope,
        public Delivery $delivery,
    ) {}

    /**
     * A checked declaration. With no scope the listener covers every change;
     * with no delivery a Post listener runs in the background and any other
     * immediately.
     *
     * @param class-string<EventInterface>    $event
     * @param class-string<ListenerInterface> $listener
     *
     * @throws InvalidDeclarationException When the name is malformed, a class is the wrong kind, or the scope or delivery doesn't suit the event.
     */
    public static function of(
        string $name,
        string $event,
        string $listener,
        ?Scope $scope = null,
        ?Delivery $delivery = null,
    ): self {
        Names::name('Listener', $name);
        Names::implementing('Event', $event, EventInterface::class);
        Names::implementing('Listener', $listener, ListenerInterface::class);

        $scope ??= Scope::all();
        if (null !== $scope->fields && ! is_a($event, ChangeEventInterface::class, allow_string: true)) {
            throw InvalidDeclarationException::because(
                sprintf('Listener "%s" has a field scope, but %s is not about changing values', $name, $event),
            );
        }

        $post = is_a($event, PostEventInterface::class, allow_string: true);
        if (Delivery::Background === $delivery && ! $post) {
            throw InvalidDeclarationException::because(
                sprintf('Listener "%s" cannot run in the background: only Post listeners can', $name),
            );
        }

        return new self(
            $name,
            $event,
            $listener,
            $scope,
            $delivery ?? ($post ? Delivery::Background : Delivery::Immediate),
        );
    }
}
