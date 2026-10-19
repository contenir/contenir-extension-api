<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Operation;

/**
 * A side effect an extension runs when its settings section is saved (persist
 * to a backend, regenerate derived assets, …). Replaces the ZF1 SplObserver
 * "registrar" coupling with a framework-agnostic hook; the host bridges it onto
 * whatever save mechanism it uses.
 *
 * @api
 */
interface SaveListenerInterface
{
    public function onSave(SaveContextInterface $context): void;
}
