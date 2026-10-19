<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Context;

use Contenir\Extension\Api\Handle;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * What the host gives an add-on's code whenever it runs: in a handler, a
 * check, a fix, a field type, a scheduled task or a script provider.
 *
 * @api
 */
interface ExtensionContextInterface
{
    /**
     * The time, for code that needs it.
     */
    public function clock(): ClockInterface;

    /**
     * The add-on the code belongs to.
     */
    public function handle(): Handle;

    /**
     * A logger whose entries carry the add-on as their source
     * (extension.<handle>).
     */
    public function logger(): LoggerInterface;

    /**
     * The host's services.
     */
    public function services(): ContainerInterface;

    /**
     * The root of the site being managed.
     */
    public function sitePath(): string;
}
