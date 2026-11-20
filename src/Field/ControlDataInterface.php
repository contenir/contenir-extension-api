<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Field;

use Contenir\Extension\Api\Context\ExtensionContextInterface;

/**
 * A field type whose control, on a record's edit form, needs data about that record that its own value doesn't
 * hold, such as what the record's other fields give it by default. A host that implements this asks for it as it
 * draws the form, and writes it on the control as JSON, in a `data-control` attribute, for the type's component
 * to read. One that doesn't leaves the attribute out, so a component must work without it.
 *
 * The data describes the record as it was last saved; a change made in the open form isn't in it.
 *
 * @api
 */
interface ControlDataInterface extends FieldTypeInterface
{
    /**
     * @param string               $section The record's section, as SectionsInterface names it, or '' for a host that doesn't know it
     * @param array<string, mixed> $record  The record's values by column name, or none for a new record
     *
     * @return array<string, mixed> Plain data that encodes as JSON; none for nothing to say
     */
    public function controlData(
        string $section,
        mixed $value,
        array $record,
        ExtensionContextInterface $context,
    ): array;
}
