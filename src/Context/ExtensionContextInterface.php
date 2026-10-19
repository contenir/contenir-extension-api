<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Context;

use Contenir\Extension\Api\Exception\UnknownSettingException;
use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Settings\SettingsInterface;
use Contenir\Extension\Api\Url\UrlResolverInterface;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
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
     * Fires the add-on's own events, under its Event\ namespace, to the
     * listeners sites have bound. Dispatching a core event throws: only the
     * CMS fires those. Each dispatch counts towards the chain's depth, and the
     * same event for the same subject cannot recur within one chain.
     */
    public function events(): EventDispatcherInterface;

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
     * One of the add-on's own settings sections, to read and write; by
     * default the one named after its handle.
     *
     * @throws UnknownSettingException When the add-on declared no such section.
     */
    public function settings(?string $section = null): SettingsInterface;

    /**
     * The root of the site being managed.
     */
    public function sitePath(): string;

    /**
     * Where records appear on the public site.
     */
    public function urls(): UrlResolverInterface;
}
