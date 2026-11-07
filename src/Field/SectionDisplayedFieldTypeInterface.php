<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Field;

use Contenir\Extension\Api\Context\ExtensionContextInterface;

/**
 * A displayed field type that also needs to know which section the record is in, as a type whose lines depend on
 * how the section's own configuration is set. A host that knows the section calls displayIn() in place of
 * display(); one that doesn't calls display().
 *
 * @api
 */
interface SectionDisplayedFieldTypeInterface extends DisplayedFieldTypeInterface
{
    /**
     * @param string               $section The record's section, as SectionsInterface names it
     * @param array<string, mixed> $record  The record's values by column name
     *
     * @return list<FieldDisplayLine> None to draw the value as plain text
     */
    public function displayIn(string $section, mixed $value, array $record, ExtensionContextInterface $context): array;
}
