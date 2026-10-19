<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Operation;

/**
 * A minimal, framework-free read projection of the settings section an
 * operation or listener is acting on.
 *
 * @api
 */
interface SectionViewInterface
{
    public function category(): string;

    public function hasField(string $name): bool;

    public function id(): string;

    public function resourceId(): string;
}
