<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Operation;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Passed to a SaveListenerInterface when its section's settings form is saved.
 * Framework-free: listeners read submitted values and reach services/logger
 * without touching the host's form or controller.
 *
 * @api
 */
interface SaveContextInterface
{
    public function logger(): LoggerInterface;

    /**
     * The per-field override map from the form (the `override[]` POST): a field
     * name is present and truthy when the operator chose to override it locally
     * rather than inherit the global value.
     *
     * @return array<string, mixed>
     */
    public function overrides(): array;

    public function section(): SectionViewInterface;

    public function services(): ContainerInterface;

    /**
     * @return array<string, mixed>
     */
    public function values(): array;
}
