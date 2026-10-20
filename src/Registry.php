<?php

declare(strict_types=1);

namespace Contenir\Extension\Api;

use Contenir\Extension\Api\Diagnostic\CheckInterface;
use Contenir\Extension\Api\Diagnostic\DiagnosticDeclaration;
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Field\FieldTypeDeclaration;
use Contenir\Extension\Api\Field\FieldTypeInterface;
use Contenir\Extension\Api\Handler\HandlerDeclaration;
use Contenir\Extension\Api\Handler\HandlerInterface;
use Contenir\Extension\Api\Handler\WebhookInterface;
use Contenir\Extension\Api\Internal\Names;
use Contenir\Extension\Api\Listener\Delivery;
use Contenir\Extension\Api\Listener\ListenerDeclaration;
use Contenir\Extension\Api\Listener\ListenerInterface;
use Contenir\Extension\Api\Listener\Scope;
use Contenir\Extension\Api\Migration\MigrationSet;
use Contenir\Extension\Api\Migration\Target;
use Contenir\Extension\Api\Module\Component;
use Contenir\Extension\Api\Module\Module;
use Contenir\Extension\Api\Panel\PanelDeclaration;
use Contenir\Extension\Api\Panel\RecordPanelInterface;
use Contenir\Extension\Api\Schedule\Frequency;
use Contenir\Extension\Api\Schedule\ScheduleDeclaration;
use Contenir\Extension\Api\Schedule\ScheduledTaskInterface;
use Contenir\Extension\Api\Script\ScriptDeclaration;
use Contenir\Extension\Api\Script\ScriptProviderInterface;
use Contenir\Extension\Api\Settings\SettingsSection;

use function array_key_exists;
use function array_values;
use function sprintf;
use function str_starts_with;

/**
 * The only door from an add-on into the CMS. The host gives each add-on its
 * own registry in ExtensionInterface::register(); each method declares one
 * thing the add-on adds, and registration() hands the host everything
 * declared. Declarations are checked as they are made, so a mistake fails
 * register() and the host skips the add-on rather than half-loading it.
 *
 * @api
 *
 * @mago-expect lint:too-many-methods One method per extension point, as the plan's Registry table lists them.
 * @mago-expect lint:too-many-properties One property per extension point.
 */
final class Registry
{
    /**
     * The name no handler may take: extension.<handle>.fix guards applying
     * the add-on's diagnostic fixes.
     */
    public const string FIX = 'fix';

    private readonly Handle $handle;

    /**
     * @var array<string, SettingsSection>
     */
    private array $settingsSections = [];

    /**
     * @var array<string, FieldTypeDeclaration>
     */
    private array $fieldTypes = [];

    private ?string $icons = null;

    /**
     * @var array<string, DiagnosticDeclaration>
     */
    private array $diagnostics = [];

    /**
     * @var array<string, ListenerDeclaration> By name and event
     */
    private array $listeners = [];

    /**
     * @var array<string, HandlerDeclaration>
     */
    private array $handlers = [];

    /**
     * @var array<string, HandlerDeclaration>
     */
    private array $webhooks = [];

    /**
     * @var array<string, MigrationSet>
     */
    private array $migrations = [];

    /**
     * @var array<string, ScheduleDeclaration>
     */
    private array $schedules = [];

    /**
     * @var array<string, ScriptDeclaration>
     */
    private array $scripts = [];

    /**
     * @var array<string, PanelDeclaration>
     */
    private array $panels = [];

    private ?string $adminXml = null;

    private ?string $assets = null;

    /**
     * @var array<string, Component>
     */
    private array $components = [];

    private ?string $templates = null;

    /**
     * @var array<string, Handle>
     */
    private array $dependencies = [];

    /**
     * @throws InvalidDeclarationException When the handle is malformed or reserved.
     */
    public function __construct(Handle|string $handle)
    {
        $this->handle = $handle instanceof Handle ? $handle : new Handle($handle);
    }

    /**
     * A folder of built scripts and styles, published under
     * /cms/ext/<handle>/.
     *
     * @throws InvalidDeclarationException When the path is empty or assets are already declared.
     */
    public function assets(string $path): void
    {
        $this->assets = $this->once('assets', $this->assets, $path);
    }

    /**
     * A dashboard component: the elements matching $selector are enhanced by
     * $script, a path within the add-on's assets.
     *
     * @throws InvalidDeclarationException When either is empty or the selector is taken.
     */
    public function component(string $selector, string $script): void
    {
        Names::text('A component selector', $selector);
        Names::text('A component script', $script);
        $this->components[$this->free('component', $selector, $this->components)] = new Component($selector, $script);
    }

    /**
     * Another add-on this one needs, whose Event\ and Api\ namespaces it may
     * use. Disabling that add-on warns about this one.
     *
     * @throws InvalidDeclarationException When the handle is malformed, this add-on's own, or already declared.
     */
    public function dependsOn(string $handle): void
    {
        $dependency = new Handle($handle);
        if ($dependency->value === $this->handle->value) {
            throw InvalidDeclarationException::because(sprintf('Add-on "%s" cannot depend on itself', $handle));
        }

        $this->dependencies[$this->free('dependency', $handle, $this->dependencies)] = $dependency;
    }

    /**
     * A check in the update check.
     *
     * @param class-string<CheckInterface> $class
     *
     * @throws InvalidDeclarationException When the name is malformed or taken, or the class is not a check.
     */
    public function diagnostic(string $name, string $class): void
    {
        Names::name('Diagnostic', $name);
        $this->diagnostics[$this->free('diagnostic', $name, $this->diagnostics)] = new DiagnosticDeclaration(
            $name,
            Names::implementing('Diagnostic', $class, CheckInterface::class),
        );
    }

    /**
     * A field type any section can use. Sections name it with the add-on's
     * handle in front, as <handle>:<name> (mailchimp:optin), so add-ons'
     * types never clash with each other's or with the built-in ones.
     *
     * @param class-string<FieldTypeInterface> $class
     *
     * @throws InvalidDeclarationException When the name is malformed or taken, or the class is not a field type.
     */
    public function fieldType(string $name, string $class): void
    {
        $type = $this->handle->fieldType(Names::name('Field type', $name));

        $this->fieldTypes[$this->free('field type', $type, $this->fieldTypes)] = new FieldTypeDeclaration(
            $type,
            $name,
            Names::implementing('Field type', $class, FieldTypeInterface::class),
        );
    }

    /**
     * The add-on this registry belongs to.
     */
    public function handle(): Handle
    {
        return $this->handle;
    }

    /**
     * An admin handler, mounted at /admin/extension/<handle>/<name> behind
     * the ACL resource extension.<handle>.<name>.
     *
     * @param class-string<HandlerInterface> $class
     *
     * @throws InvalidDeclarationException When the name is malformed, reserved or taken, or the class is not a handler.
     */
    public function handler(string $name, string $class): void
    {
        Names::name('Handler', $name);
        if (self::FIX === $name) {
            throw InvalidDeclarationException::because(sprintf('Handler name "%s" is reserved', $name));
        }

        $this->handlers[$this->free('handler', $name, $this->handlers)] = new HandlerDeclaration(
            $name,
            Names::implementing('Handler', $class, HandlerInterface::class),
            sprintf('/admin/extension/%s/%s', $this->handle->value, $name),
        );
    }

    /**
     * A folder of SVG icons, usable by name like the host's own.
     *
     * @throws InvalidDeclarationException When the path is empty or icons are already declared.
     */
    public function icons(string $path): void
    {
        $this->icons = $this->once('icons', $this->icons, $path);
    }

    /**
     * A listener: what the add-on can do when $event fires. A site binds it
     * to sections in admin.xml, and a section with no binding never calls it.
     * $scope is the widest field scope a binding may ask for; a field scope
     * needs an event about changing values. Post listeners run in the
     * background unless $delivery asks for Immediate; listeners at other
     * stages always run immediately. One name may listen to several events.
     *
     * @param class-string<EventInterface>    $event
     * @param class-string<ListenerInterface> $listener
     *
     * @throws InvalidDeclarationException When the name is malformed, a class is the wrong kind, the scope or delivery doesn't suit the event, or the name already listens to the event.
     */
    public function listen(
        string $name,
        string $event,
        string $listener,
        ?Scope $scope = null,
        ?Delivery $delivery = null,
    ): void {
        $declaration = ListenerDeclaration::of($name, $event, $listener, $scope, $delivery);

        $key = "{$name} {$event}";
        if (array_key_exists($key, $this->listeners)) {
            throw InvalidDeclarationException::because(sprintf('Listener "%s" already listens to %s', $name, $event));
        }

        $this->listeners[$key] = $declaration;
    }

    /**
     * A folder of migrations creating the add-on's ext_<handle>_ tables, in
     * the site's database unless $target says the CMS's.
     *
     * @throws InvalidDeclarationException When the path is empty, the namespace malformed, or the namespace already declared.
     */
    public function migrations(string $path, string $namespace, Target $target = Target::Site): void
    {
        Names::text('A migrations path', $path);
        Names::namespace($namespace);
        $this->migrations[$this->free('migration namespace', $namespace, $this->migrations)] = new MigrationSet(
            $path,
            $namespace,
            $target,
        );
    }

    /**
     * Makes the add-on a module: an admin area of its own, from an admin.xml
     * fragment of categories and sections.
     *
     * @throws InvalidDeclarationException When the path is empty or a module is already declared.
     */
    public function module(string $adminXml): void
    {
        $this->adminXml = $this->once('module', $this->adminXml, $adminXml);
    }

    /**
     * A panel beside one record on its view and edit screens. A site binds it
     * to the sections it should show on; a section with no binding never
     * asks for it.
     *
     * @param class-string<RecordPanelInterface> $class
     *
     * @throws InvalidDeclarationException When the name is malformed or taken, or the class is not a record panel.
     */
    public function panel(string $name, string $class): void
    {
        Names::name('Panel', $name);
        $this->panels[$this->free('panel', $name, $this->panels)] = new PanelDeclaration(
            $name,
            Names::implementing('Record panel', $class, RecordPanelInterface::class),
        );
    }

    /**
     * Everything declared so far.
     */
    public function registration(): Registration
    {
        return new Registration(
            handle: $this->handle,
            settingsSections: $this->settingsSections,
            fieldTypes: $this->fieldTypes,
            icons: $this->icons,
            diagnostics: $this->diagnostics,
            listeners: array_values($this->listeners),
            handlers: $this->handlers,
            webhooks: $this->webhooks,
            migrations: array_values($this->migrations),
            schedules: $this->schedules,
            scripts: $this->scripts,
            module: new Module($this->adminXml, $this->assets, array_values($this->components), $this->templates),
            dependencies: $this->dependencies,
            panels: $this->panels,
        );
    }

    /**
     * Recurring work for the host's job runner.
     *
     * @param class-string<ScheduledTaskInterface> $class
     *
     * @throws InvalidDeclarationException When the name is malformed or taken, or the class is not a scheduled task.
     */
    public function schedule(string $name, string $class, Frequency $frequency): void
    {
        Names::name('Schedule', $name);
        $this->schedules[$this->free('schedule', $name, $this->schedules)] = new ScheduleDeclaration(
            $name,
            Names::implementing('Scheduled task', $class, ScheduledTaskInterface::class),
            $frequency,
        );
    }

    /**
     * Scripts for public pages, each with its placement and consent category.
     *
     * @param class-string<ScriptProviderInterface> $class
     *
     * @throws InvalidDeclarationException When the name is malformed or taken, or the class is not a script provider.
     */
    public function script(string $name, string $class): void
    {
        Names::name('Script', $name);
        $this->scripts[$this->free('script', $name, $this->scripts)] = new ScriptDeclaration(
            $name,
            Names::implementing('Script provider', $class, ScriptProviderInterface::class),
        );
    }

    /**
     * A Site Settings section with its operations, contributions, save
     * listeners and row hydrators. Its name is the add-on's handle, or the
     * handle followed by a dash and more, so it never takes a core section's
     * name.
     *
     * @throws InvalidDeclarationException When the name doesn't start with the handle or is taken.
     */
    public function settingsSection(SettingsSection $section): void
    {
        $handle = $this->handle->value;
        if ($handle !== $section->name && ! str_starts_with($section->name, "{$handle}-")) {
            throw InvalidDeclarationException::because(
                sprintf(
                    'Settings section "%s" must be named "%s" or start with "%s-"',
                    $section->name,
                    $handle,
                    $handle,
                ),
            );
        }

        $this->settingsSections[$this->free('settings section', $section->name, $this->settingsSections)] = $section;
    }

    /**
     * A folder of templates for a module's screens that can't be described.
     * Only modules may ship templates, so declare module() first.
     *
     * @throws InvalidDeclarationException When the add-on is not a module, the path is empty, or templates are already declared.
     */
    public function templates(string $path): void
    {
        if (null === $this->adminXml) {
            throw InvalidDeclarationException::because('Only a module may ship templates: declare module() first');
        }

        $this->templates = $this->once('templates', $this->templates, $path);
    }

    /**
     * A public webhook, mounted at /api/extension/<handle>/<name>, which must
     * verify its caller itself.
     *
     * @param class-string<WebhookInterface> $class
     *
     * @throws InvalidDeclarationException When the name is malformed or taken, or the class is not a webhook.
     */
    public function webhook(string $name, string $class): void
    {
        Names::name('Webhook', $name);
        $this->webhooks[$this->free('webhook', $name, $this->webhooks)] = new HandlerDeclaration(
            $name,
            Names::implementing('Webhook', $class, WebhookInterface::class),
            sprintf('/api/extension/%s/%s', $this->handle->value, $name),
        );
    }

    /**
     * $name, failing when $taken already holds it.
     *
     * @param array<string, mixed> $taken
     *
     * @throws InvalidDeclarationException
     */
    private function free(string $kind, string $name, array $taken): string
    {
        if (array_key_exists($name, $taken)) {
            throw InvalidDeclarationException::duplicate($kind, $name);
        }

        return $name;
    }

    /**
     * $path for a one-off declaration, failing when it is empty or $current
     * is already set.
     *
     * @throws InvalidDeclarationException
     */
    private function once(string $kind, ?string $current, string $path): string
    {
        if (null !== $current) {
            throw InvalidDeclarationException::because(sprintf('The add-on has already declared its %s', $kind));
        }

        return Names::text(sprintf('The %s path', $kind), $path);
    }
}
