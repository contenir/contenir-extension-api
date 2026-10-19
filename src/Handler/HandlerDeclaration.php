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
     * @param string                                                        $path  Where the host mounts it
     */
    public function __construct(
        public string $name,
        public string $class,
        public string $path,
    ) {}
}
