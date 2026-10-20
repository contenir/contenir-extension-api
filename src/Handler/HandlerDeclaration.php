<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Handler;

/**
 * An admin handler or a public webhook an add-on registered.
 *
 * @api
 */
final readonly class HandlerDeclaration
{
    /**
     * @param class-string<HandlerInterface>|class-string<WebhookInterface> $class
     * @param string                                                        $path      Where the host mounts it
     * @param Privilege|null                                                $privilege What an admin handler needs, whatever the method; null to go by the method
     */
    public function __construct(
        public string $name,
        public string $class,
        public string $path,
        public ?Privilege $privilege = null,
    ) {}

    /**
     * What a request with $method needs to run the handler.
     */
    public function privilegeFor(string $method): Privilege
    {
        return $this->privilege ?? Privilege::forMethod($method);
    }
}
