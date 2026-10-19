<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Operation;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Passed to a RowHydratorInterface so it can overlay transient (non-file-backed)
 * field values onto the settings row before the view/form reads them, without
 * touching host row/section types directly.
 *
 * @api
 */
interface RowHydrationContextInterface
{
    public function hasField(string $name): bool;

    public function logger(): LoggerInterface;

    /**
     * Mark a (typically transient) field as overridable so the form renders the
     * inherit shell: records the global fallback value and whether the field is
     * currently locally overridden.
     *
     * @mago-expect lint:no-boolean-flag-parameter Kept as the legacy CMS declares it, so its alias keeps working.
     */
    public function markOverridable(string $name, mixed $globalValue, bool $overridden): void;

    public function services(): ContainerInterface;

    public function setField(string $name, mixed $value): void;
}
