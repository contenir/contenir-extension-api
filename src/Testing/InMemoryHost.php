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
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Event\Extension\ExtensionPostFail;
use Contenir\Extension\Api\Event\Extension\FailurePoint;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Exception\UnknownSettingException;
use Contenir\Extension\Api\ExtensionInterface;
use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Module\MenuGroup;
use Contenir\Extension\Api\Module\MenuInterface;
use Contenir\Extension\Api\Operation\OperationResultInterface;
use Contenir\Extension\Api\Queue\TaskQueueInterface;
use Contenir\Extension\Api\Record\LinkableSection;
use Contenir\Extension\Api\Record\RecordsInterface;
use Contenir\Extension\Api\Record\RecordUpdatesInterface;
use Contenir\Extension\Api\Registration;
use Contenir\Extension\Api\Registry;
use Contenir\Extension\Api\Section\Element;
use Contenir\Extension\Api\Section\SectionsInterface;
use Contenir\Extension\Api\Settings\SettingsInterface;
use Contenir\Extension\Api\Site\SiteAnswer;
use Contenir\Extension\Api\Site\SiteRequestsInterface;
use Contenir\Extension\Api\Testing\Internal\AddOnEvents;
use Contenir\Extension\Api\Testing\Internal\AdminHandlers;
use Contenir\Extension\Api\Testing\Internal\Answers;
use Contenir\Extension\Api\Testing\Internal\Bindings;
use Contenir\Extension\Api\Testing\Internal\BoundListener;
use Contenir\Extension\Api\Testing\Internal\Chain;
use Contenir\Extension\Api\Testing\Internal\Checks;
use Contenir\Extension\Api\Testing\Internal\Dispatcher;
use Contenir\Extension\Api\Testing\Internal\HostRecords;
use Contenir\Extension\Api\Testing\Internal\HostRecordUpdates;
use Contenir\Extension\Api\Testing\Internal\HostSections;
use Contenir\Extension\Api\Testing\Internal\HostSite;
use Contenir\Extension\Api\Testing\Internal\Operations;
use Contenir\Extension\Api\Testing\Internal\ProvidedServices;
use Contenir\Extension\Api\Testing\Internal\QueuedTasks;
use Contenir\Extension\Api\Testing\Internal\Records;
use Contenir\Extension\Api\Testing\Internal\Responder;
use Contenir\Extension\Api\Testing\Internal\Runtime;
use Contenir\Extension\Api\Testing\Internal\Saver;
use Contenir\Extension\Api\Testing\Internal\SectionBlocks;
use Contenir\Extension\Api\Testing\Internal\Services;
use Contenir\Extension\Api\Testing\Internal\Settings;
use Contenir\Extension\Api\Testing\Internal\SignedIn;
use Contenir\Extension\Api\Testing\Internal\Webhooks;
use Contenir\Extension\Api\Url\RecordReference;
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
 * editor a request was answered for, and no one outside a request, and
 * Site\SiteRequestsInterface, with a site answering as answerSite() says.
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

    private readonly HostSite $hostSite;

    private readonly HostRecords $hostRecords;

    private readonly HostSections $sections;

    private readonly QueuedTasks $queued;

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
            fn(Handle $handle): SectionsInterface => $this->sections->for($this->registrations[$handle->value] ?? null),
        );
        $this->dispatcher  = new Dispatcher($this->bindings, $this->runtime);
        $this->checks      = new Checks($this->runtime);
        $this->operations  = new Operations($this->runtime);
        $this->provided    = new ProvidedServices($this->runtime);
        $this->signedIn    = new SignedIn();
        $this->hostRecords = new HostRecords($this->records, $this->dispatcher, $this->signedIn);
        $this->sections    = new HostSections(
            new SectionBlocks(),
            $this->records,
            $this->hostRecords,
            $this->runtime,
            $this->bindings,
            $this->resolve(...),
        );
        $this->hostSite = new HostSite($this->records);
        $this->queued   = new QueuedTasks($this->runtime, $this->signedIn, $this->registration(...));
        $this->services->add([
            RecordsInterface::class       => $this->hostRecords,
            RecordUpdatesInterface::class => new HostRecordUpdates($this->records, $this->dispatcher, $this->signedIn),
            CurrentEditorInterface::class => $this->signedIn,
            SiteRequestsInterface::class  => $this->hostSite,
            TaskQueueInterface::class     => $this->queued,
        ]);

        $responder     = new Responder($responses, $streams);
        $this->answers = new Answers(
            new AdminHandlers($this->runtime, $responder),
            new Webhooks($this->runtime, $responder),
            $responder,
        );
    }

    /**
     * Has the site answer $path, for an add-on's tests. A path with no answer
     * is one the site didn't answer.
     *
     * @throws InvalidDeclarationException When the path isn't one of the site's /api/ paths.
     */
    public function answerSite(string $path, SiteAnswer $answer): void
    {
        $this->hostSite->answer($path, $answer);
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
        [$class, $bound] = $this->resolve($handle, $listener, $event, $fields);
        $this->bindings->add($section, $class, $bound, $order);
    }

    /**
     * Puts the element in $xml inside $section's <extensions>, with the
     * section's fields, and binds the listeners it implies when the add-on
     * it names is loaded: so call it after boot().
     *
     * @param list<string> $fields
     *
     * @throws InvalidDeclarationException When $xml is not one well-formed element.
     */
    #[Override]
    public function block(string $section, string $xml, array $fields = []): void
    {
        $element = Element::parse($xml);
        $this->sections->put($section, $element, $fields, $this->registrations[$element->name] ?? null);
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

    /**
     * Runs the tasks add-ons have queued, each as the editor who queued it, and what they queue in turn.
     *
     * @return int How many tasks ran
     */
    public function runQueued(): int
    {
        return $this->queued->run();
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

    /**
     * What add-ons have asked the site, for an add-on's tests.
     *
     * @return list<array{record: RecordReference, path: string, fields: array<string, string>}>
     */
    public function sitePosts(): array
    {
        return $this->hostSite->posts();
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

    /**
     * The event class and bound listener a line names.
     *
     * @param list<string> $fields
     *
     * @return array{class-string<EventInterface>, BoundListener}
     *
     * @throws InvalidDeclarationException When the add-on is not loaded or declared no such listener, or the line widens its scope.
     */
    private function resolve(string $handle, string $listener, string $event, array $fields): array
    {
        $class        = Catalogue::find($event) ?? $event;
        $registration = $this->registrations[$handle] ?? throw InvalidDeclarationException::because(
                sprintf('No add-on "%s" is loaded', $handle),
            );

        foreach ($registration->listeners as $declaration) {
            if ($listener === $declaration->name && $class === $declaration->event) {
                return [
                    $declaration->event,
                    new BoundListener($registration->handle, $declaration, $declaration->scope->narrow(...$fields)),
                ];
            }
        }

        throw InvalidDeclarationException::because(
            sprintf('Add-on "%s" has no listener "%s" for %s', $handle, $listener, $event),
        );
    }
}
