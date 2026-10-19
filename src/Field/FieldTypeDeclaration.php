<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Field;

/**
 * A field type an add-on registered, under the name sections use for it.
 *
 * @api
 */
final readonly class FieldTypeDeclaration
{
    /**
     * @param class-string<FieldTypeInterface> $class
     */
    public function __construct(
        public string $type,
        public string $class,
    ) {}
}
