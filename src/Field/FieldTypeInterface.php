<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Field;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Form\FieldType;

/**
 * A field type an add-on brings, usable in any section once registered with
 * Registry::fieldType(). The host draws it with one of its own controls,
 * optionally enhanced by a dashboard component the add-on registered, so it
 * matches the rest of the admin on every host.
 *
 * @api
 */
interface FieldTypeInterface
{
    /**
     * The selector of a component registered with Registry::component() that
     * enhances the control, or null to use the control as it is.
     */
    public function component(): ?string;

    /**
     * The built-in control the host draws the field with.
     */
    public function control(): FieldType;

    /**
     * The value to store for what the editor submitted.
     */
    public function normalise(mixed $value): mixed;

    /**
     * Choices for a select, radio or multi-choice control, value => label.
     *
     * @return array<string, string>
     */
    public function options(ExtensionContextInterface $context): array;

    /**
     * What is wrong with a submitted value, as messages the form shows;
     * empty when it is valid.
     *
     * @return list<string>
     */
    public function validate(mixed $value): array;
}
