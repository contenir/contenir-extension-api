<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\ExtensionInterface;
use Contenir\Extension\Api\Registry;
use Override;
use RuntimeException;

/**
 * An add-on whose register() throws, as one with a missing dependency might.
 *
 * @api
 */
final class ThrowingExtension implements ExtensionInterface
{
    public const string MESSAGE = 'The throwing add-on could not register';

    /**
     * @throws RuntimeException Always.
     */
    #[Override]
    public function register(Registry $registry): void
    {
        throw new RuntimeException(self::MESSAGE);
    }
}
