<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Closure;
use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Event\Extension\ExtensionPostFail;
use Contenir\Extension\Api\Event\Extension\FailurePoint;
use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Log\SourceLogger;
use Contenir\Extension\Api\Settings\SettingsInterface;
use Contenir\Extension\Api\Testing\InMemoryContext;
use Contenir\Extension\Api\Url\UrlResolverInterface;
use LogicException;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use function sprintf;
use function strrchr;
use function substr;

/**
 * What every piece of the in-memory host shares: add-ons' contexts, how
 * their classes are made, and how their failures are reported.
 *
 * @internal
 */
final class Runtime
{
    /**
     * @var array<string, ExtensionContextInterface>
     */
    private array $contexts = [];

    /**
     * @var list<ExtensionPostFail>
     */
    private array $failures = [];

    /**
     * @param Closure(): EventDispatcherInterface $events   A dispatcher for add-ons' own events
     * @param Closure(ExtensionPostFail): void    $announce Fires a failure to the listeners bound to it
     * @param Closure(Handle, string): SettingsInterface $settings Opens one of an add-on's settings sections
     *
     * @mago-expect lint:excessive-parameter-list One parameter per part of an add-on's context.
     */
    public function __construct(
        private readonly ContainerInterface $services,
        private readonly LoggerInterface $logger,
        private readonly ClockInterface $clock,
        private readonly string $sitePath,
        private readonly UrlResolverInterface $urls,
        private readonly Closure $events,
        private readonly Closure $announce,
        private readonly Closure $settings,
    ) {}

    private static function shortName(string $class): string
    {
        $short = strrchr($class, needle: '\\');

        return false === $short ? $class : substr($short, offset: 1);
    }

    public function context(Handle $handle): ExtensionContextInterface
    {
        return $this->contexts[$handle->value] ??= new InMemoryContext(
            $handle,
            $this->services,
            new SourceLogger($this->logger, $handle),
            $this->clock,
            $this->sitePath,
            $this->urls,
            ($this->events)(),
            fn(string $section): SettingsInterface => ($this->settings)($handle, $section),
        );
    }

    /**
     * @return list<ExtensionPostFail>
     */
    public function failures(): array
    {
        return $this->failures;
    }

    /**
     * An instance of an add-on's class: the service of that name when there
     * is one, or the class constructed with no arguments.
     *
     * @template T of object
     *
     * @param class-string    $class
     * @param class-string<T> $type
     *
     * @return T
     *
     * @throws LogicException When the class can't be made or is not a $type.
     *
     * @mago-expect analysis:unknown-class-instantiation,mixed-assignment Add-on classes are named by string; the instance is checked below.
     */
    public function make(string $class, string $type): object
    {
        try {
            $instance = $this->services->has($class) ? $this->services->get($class) : new $class();
        } catch (ContainerExceptionInterface $exception) {
            throw new LogicException(sprintf('Could not make %s', $class), previous: $exception);
        }

        if (! $instance instanceof $type) {
            throw new LogicException(sprintf('%s is not a %s', $class, $type));
        }

        return $instance;
    }

    /**
     * Logs a failure and keeps its ExtensionPostFail without firing it. A
     * failure in a listener to ExtensionPostFail itself goes only this far,
     * since firing it would announce itself for ever.
     */
    public function record(Handle $handle, FailurePoint $point, string $name, Throwable $failure): ExtensionPostFail
    {
        $summary = sprintf('%s: %s', self::shortName($failure::class), $failure->getMessage());

        $this->context($handle)
            ->logger()
            ->error(
                sprintf('The %s "%s" failed', $point->value, $name),
                ['point' => $point->value, 'name' => $name, 'summary' => $summary],
            );

        $event            = new ExtensionPostFail($handle, $point, $name, $summary);
        $this->failures[] = $event;

        return $event;
    }

    /**
     * Logs a failure in an add-on's code and fires ExtensionPostFail, then
     * returns the failure's safe summary.
     */
    public function report(Handle $handle, FailurePoint $point, string $name, Throwable $failure): string
    {
        $event = $this->record($handle, $point, $name, $failure);
        ($this->announce)($event);

        return $event->reason();
    }
}
