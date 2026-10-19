<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Settings;

use Contenir\Extension\Api\Event\ActionResult;
use Contenir\Extension\Api\Exception\UnknownSettingException;

/**
 * One of the add-on's own settings sections, as its code reads and writes
 * it: the values an editor saved on the settings screen, with password fields
 * read from the site's .local.php.
 *
 * Writing saves as the settings screen does: the host fires
 * SettingsPreSave, SettingsSave and SettingsPostSave, so listeners and save
 * listeners run, and it stores password fields in .local.php.
 *
 * @api
 */
interface SettingsInterface
{
    /**
     * Every field's value, by field name.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * A field's value, or $default when it has none.
     *
     * @throws UnknownSettingException When the section has no such field.
     */
    public function get(string $field, mixed $default = null): mixed;

    /**
     * The section's name.
     */
    public function section(): string;

    /**
     * Saves values, as the settings screen would. Fields not given keep
     * their values.
     *
     * @param array<string, mixed> $values By field name
     *
     * @throws UnknownSettingException When the section has no such field.
     */
    public function set(array $values): ActionResult;
}
