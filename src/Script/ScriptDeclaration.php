<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Script;

/**
 * A script provider an add-on registered.
 *
 * @api
 */
final readonly class ScriptDeclaration
{
    /**
     * @param class-string<ScriptProviderInterface> $class
     */
    public function __construct(
        public string $name,
        public string $class,
    ) {}
}
