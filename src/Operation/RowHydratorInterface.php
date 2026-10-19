<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Operation;

/**
 * Populates an extension's transient fields on the settings row from the backend
 * the extension owns (a repository, a config file). Runs on the view and update
 * pages so both the rendered values and the form defaults are correct.
 *
 * @api
 */
interface RowHydratorInterface
{
    public function hydrate(RowHydrationContextInterface $context): void;
}
