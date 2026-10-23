<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing;

use Contenir\Extension\Api\Conformance\HostDriverInterface;
use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Diagnostic\CheckResult;
use Contenir\Extension\Api\Editor\CurrentEditorInterface;
use Contenir\Extension\Api\Editor\Editor;
use Contenir\Extension\Api\Event\ActionResult;
use Contenir\Extension\Api\Event\Catalogue;
use Contenir\Extension\Api\Event\Extension\ExtensionPostFail;
use Contenir\Extension\Api\Event\Extension\FailurePoint;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Exception\UnknownSettingException;
use Contenir\Extension\Api\ExtensionInterface;
use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Module\MenuGroup;
use Contenir\Extension\Api\Module\MenuInterface;
use Contenir\Extension\Api\Operation\OperationResultInterface;
use Contenir\Extension\Api\Record\LinkableSection;
use Contenir\Extension\Api\Record\RecordsInterface;
use Contenir\Extension\Api\Registration;
use Contenir\Extension\Api\Registry;
use Contenir\Extension\Api\Settings\SettingsInterface;
use Contenir\Extension\Api\Testing\Internal\AddOnEvents;
use Contenir\Extension\Api\Testing\Internal\AdminHandlers;
use Contenir\Extension\Api\Testing\Internal\Answers;
use Contenir\Extension\Api\Testing\Internal\Bindings;
use Contenir\Extension\Api\Testing\Internal\BoundListener;
use Contenir\Extension\Api\Testing\Internal\Chain;
use Contenir\Extension\Api\Testing\Internal\Checks;
use Contenir\Extension\Api\Testing\Internal\Dispatcher;
use Contenir\Extension\Api\Testing\Internal\HostRecords;
use Contenir\Extension\Api\Testing\Internal\Operations;
use Contenir\Extension\Api\Testing\Internal\ProvidedServices;
use Contenir\Extension\Api\Testing\Internal\Records;
use Contenir\Extension\Api\Testing\Internal\Responder;
use Contenir\Extension\Api\Testing\Internal\Runtime;
use Contenir\Extension\Api\Testing\Internal\Saver;
use Contenir\Extension\Api\Testing\Internal\Services;
use Contenir\Extension\Api\Testing\Internal\Settings;
use Contenir\Extension\Api\Testing\Internal\SignedIn;
use Contenir\Extension\Api\Testing\Internal\Webhooks;
use Contenir\Extension\Api\Url\UrlResolverInterface;
use LogicException;
use Override;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;

use function array_keys;
use function in_array;
use function sprintf;

/**
 * A host with no CMS behind it, holding records and everything else in
 * memory. It passes the conformance suite, so add-ons can be tested against
 * the API without either CMS running, and it shows hosts what the API means.
 *
 * Saving a record fires RecordPreSave, RecordSave and RecordPostSave, for new
 * records as for existing ones.
 *
 * It lends add-ons its records as Record\RecordsInterface. A record's title,
 * path and status are its "title", "path" and "status" values, and each
 * linkable section is its own ACL resource. While a request is answered the
 * records act for an editor holding the request's grants; outside one, as
 * when a test calls an add-on's classes with context(), for an editor
 * allowed everything. It lends Editor\CurrentEditorInterface too, naming the
 * editor a request was answered for, and no one outside a request.
 *
 * @api
 *
 * @mago-expect lint:too-many-methods One method per thing the conformance suite drives, plus three for add-on tests.
 * @mago-expect lint:too-many-properties One property per part of the host the conformance suite drives.
 */
final class InMemoryHost implements HostDriverInterface
{
    private readonly Services $services;

    private readonly MemoryLogger $logger;

    private readonly Bindings $bindings;

    private readonly Records $records;

    private readonly Runtime $runtime;

    private readonly Dispatcher $dispatcher;

    private readonly Checks $checks;

    private readonly Answers $answers;

    private readonly Operations $operations;

    private readonly ProvidedServices $provided;

    private readonly SignedIn $signedIn;

    private readonly HostRecords $hostRecords;

    /**
     * @var array<string, Registration> By handle
     */
    private array $registrations = [];

    /**
     * @var array<string, string> By handle
     */
    private array $problems = [];

    public function __construct(
        ResponseFactoryInterface $responses,
        StreamFactoryInterface $streams,
        ClockInterface $clock,
        UrlResolverInterface $urls = new MapUrlResolver(),
        string $sitePath = '/site',
    ) {
        $this->services = new Services(new ServiceMap());
        $this->logger   = new MemoryLogger();
        $this->bindings = new Bindings();
        $this->records  = new Records();

        $chain         = new Chain();
        $this->runtime = new Runtime(
            $this->services,
            $this->logger,
            $clock,
            $sitePath,
            $urls,
            fn(): EventDispatcherInterface => new AddOnEvents($this->dispatcher, $chain),
            function (ExtensionPostFail $failure): void {
                $this->dispatcher->dispatch(null, $failure);
            },
            fn(Handle $handle, string $section): SettingsInterface => new Settings(
                $this->registration($handle->value)->settingsSections[$section] ?? throw UnknownSettingException::section(
                    $handle->value,
                    $section,
                ),
                $this->records,
                $this->dispatcher,
            ),
        );
        $this->dispatcher  = new Dispatcher($this->bindings, $this->runtime);
        $this->checks      = new Checks($this->runtime);
        $this->operations  = new Operations($this->runtime);
        $this->provided    = new ProvidedServices($this->runtime);
        $this->signedIn    = new SignedIn();
        $this->hostRecords = new HostRecords($this->records, $this->dispatcher, $this->signedIn);
        $this->services->add([
            RecordsInterface::class       => $this->hostRecords,
            CurrentEditorInterface::class => $this->signedIn,
        ]);

        $responder     = new Responder($responses, $streams);
        $this->answers = new Answers(
            new AdminHandlers($this->runtime, $responder),
            new Webhooks($this->runtime, $responder),
            $responder,
        );
    }

    /**
     * @mago-expect lint:excessive-parameter-list One parameter per attribute of an admin.xml <listen> line.
     */
    #[Override]
    public function bind(
        ?string $section,
        string $handle,
        string $listener,
        string $event,
        array $fields = [],
        ?int $order = null,
    ): void {
        $class        = Catalogue::find($event) ?? $event;
        $registration = $this->registrations[$handle] ?? throw InvalidDeclarationException::because(
                sprintf('No add-on "%s" is loaded', $handle),
            );

        foreach ($registration->listeners as $declaration) {
            if ($listener !== $declaration->name || $class !== $declaration->event) {
                continue;
            }

            $scope = $declaration->scope->narrow(...$fields);
            $this->bindings->add(
                $section,
                $class,
                new BoundListener($registration->handle, $declaration, $scope),
                $order,
            );

            return;
        }

        throw InvalidDeclarationException::because(
            sprintf('Add-on "%s" has no listener "%s" for %s', $handle, $listener, $event),
        );
    }

    #[Override]
    public function boot(array $installed, array $enabled, array $services = []): void
    {
        $this->services->add($services);

        foreach ($installed as $handle => $extension) {
            if (! in_array($handle, $enabled, strict: true)) {
                continue;
            }

            $this->load($handle, $extension);
        }

        $this->provided->warnOfRivals($this->registrations);
    }

    /**
     * @throws LogicException When the add-on is not loaded or has no such check.
     */
    #[Override]
    public function check(string $handle, string $name): CheckResult
    {
        return $this->checks->check($this->registration($handle), $name);
    }

    /**
     * The context a loaded add-on's code runs with, for calling its classes
     * directly in a test.
     *
     * @throws LogicException When the add-on is not loaded.
     */
    public function context(string $handle): ExtensionContextInterface
    {
        return $this->runtime->context($this->registration($handle)->handle);
    }

    #[Override]
    public function failures(): array
    {
        return $this->runtime->failures();
    }

    /**
     * @throws LogicException When the add-on is not loaded or has no such check.
     */
    #[Override]
    public function fix(string $handle, string $name, array $values, array $grants): ?CheckResult
    {
        $registration = $this->registration($handle);
        if (! in_array($registration->handle->fixPermission(), $grants, strict: true)) {
            return null;
        }

        return $this->checks->fix($registration, $name, $values);
    }

    #[Override]
    public function handle(
        ServerRequestInterface $request,
        array $grants = [],
        ?Editor $editor = null,
    ): ResponseInterface {
        $this->signedIn->signIn($grants, $editor);
        try {
            return $this->answers->answer($this->registrations, $request, $grants);
        } finally {
            $this->signedIn->signOut();
        }
    }

    #[Override]
    public function linkable(LinkableSection $section): void
    {
        $this->hostRecords->link($section);
    }

    #[Override]
    public function loaded(): array
    {
        return array_keys($this->registrations);
    }

    #[Override]
    public function logs(): array
    {
        return $this->logger->entries();
    }

    /**
     * What a loaded module's menu lists while $request is drawn: none when it
     * declared no menu, or when its menu failed, which is reported as the
     * host reports it.
     *
     * @return list<MenuGroup>
     *
     * @throws LogicException When the add-on is not loaded.
     */
    public function menu(string $handle, ServerRequestInterface $request): array
    {
        $registration = $this->registration($handle);
        $menu         = $registration->module->menu;
        if (null === $menu) {
            return [];
        }

        try {
            return $this->runtime
                ->make($menu, MenuInterface::class)
                ->groups($request, $this->runtime->context($registration->handle));
        } catch (Throwable $failure) {
            $this->runtime->report($registration->handle, FailurePoint::Menu, 'menu', $failure);

            return [];
        }
    }

    /**
     * @throws LogicException When the add-on is not loaded, or has no such section or operation.
     */
    #[Override]
    public function operate(
        string $handle,
        string $section,
        string $operation,
        array $posted,
        array $grants,
    ): ?OperationResultInterface {
        return $this->operations->run($this->registration($handle), $section, $operation, $posted, $grants);
    }

    #[Override]
    public function problems(): array
    {
        return $this->problems;
    }

    #[Override]
    public function provided(string $service): ?object
    {
        return $this->provided->get($this->registrations, $service);
    }

    /**
     * Everything a loaded add-on declared.
     *
     * @throws LogicException When the add-on is not loaded.
     */
    public function registration(string $handle): Registration
    {
        return $this->registrations[$handle] ?? throw new LogicException(sprintf('No add-on "%s" is loaded', $handle));
    }

    #[Override]
    public function runBackground(): void
    {
        $this->dispatcher->drain();
    }

    #[Override]
    public function save(string $section, string|int|null $id, array $values): ActionResult
    {
        return (new Saver($this->records, $this->dispatcher))->save($section, $id, $values);
    }

    #[Override]
    public function seed(string $section, string|int $id, array $values): void
    {
        $this->records->put($section, $id, $values);
    }

    #[Override]
    public function stored(string $section, string|int $id): ?array
    {
        return $this->records->get($section, $id);
    }

    /**
     * Registers one add-on, or skips it and says why when it fails.
     */
    private function load(string $handle, ExtensionInterface $extension): void
    {
        try {
            $registry = new Registry($handle);
            $extension->register($registry);
            $this->registrations[$handle] = $registry->registration();
        } catch (Throwable $failure) {
            $this->problems[$handle] = $failure->getMessage();
            $this->logger->warning(
                sprintf('Extension "%s" could not load: %s', $handle, $failure->getMessage()),
                ['source' => "extension.{$handle}"],
            );
        }
    }
}
