<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Operation;

/**
 * Implemented by extensions that run side effects on save. Discovered via
 * instanceof.
 *
 * @api
 */
interface SaveListenerProviderInterface
{
    /**
     * @return list<class-string<SaveListenerInterface>>
     */
    public function getSaveListeners(): array;
}
