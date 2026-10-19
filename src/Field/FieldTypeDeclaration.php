<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Field;

/**
 * A field type an add-on registered: the type sections use, <handle>:<name>,
 * and the name the add-on gave it.
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
        public string $name,
        public string $class,
    ) {}
}
