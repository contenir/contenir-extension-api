<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Panel;

/**
 * A record panel an add-on registered.
 *
 * @api
 */
final readonly class PanelDeclaration
{
    /**
     * @param class-string<RecordPanelInterface> $class
     */
    public function __construct(
        public string $name,
        public string $class,
    ) {}
}
