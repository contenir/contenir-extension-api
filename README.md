# contenir/contenir-extension-api

[![Continuous Integration](https://github.com/contenir/contenir-extension-api/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/contenir/contenir-extension-api/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/contenir/contenir-extension-api/graph/badge.svg)](https://codecov.io/gh/contenir/contenir-extension-api)

The add-on API for Contenir CMS. Add-ons build against this package and nothing else; each CMS implements it as a
host. So an add-on works the same whether the CMS underneath is the legacy ZF1 CMS or the Mezzio rewrite.

It holds only contracts and value objects, with no framework types: PSR-3 logging, PSR-7 requests, PSR-11 services,
PSR-14 events and PSR-20 time.

## Requirements

- PHP 8.3, 8.4 or 8.5, with the DOM extension (the in-memory host reads a module's admin.xml)

## An add-on

An add-on is a Composer package of type `contenir-extension`, named `contenir-extension/<handle>`, whose classes
live under `Contenir\Extension\<Package>\`. Its composer.json names its handle and its entry class:

```json
{
    "name": "contenir-extension/newsletter",
    "type": "contenir-extension",
    "require": {
        "contenir/contenir-extension-api": "^0.6"
    },
    "autoload": {
        "psr-4": { "Contenir\\Extension\\Newsletter\\": "src/" }
    },
    "extra": {
        "contenir": {
            "handle": "newsletter",
            "extension": "Contenir\\Extension\\Newsletter\\NewsletterExtension"
        }
    }
}
```

The entry class implements `ExtensionInterface`. The host constructs it and calls `register()` with a `Registry`
for the add-on's handle; each registry method declares one thing the add-on adds to the CMS:

```php
use Contenir\Extension\Api\ExtensionInterface;
use Contenir\Extension\Api\Form\Field;
use Contenir\Extension\Api\Form\Form;
use Contenir\Extension\Api\Registry;
use Contenir\Extension\Api\Schedule\Frequency;
use Contenir\Extension\Api\Settings\SettingsCategory;
use Contenir\Extension\Api\Settings\SettingsSection;

final class NewsletterExtension implements ExtensionInterface
{
    public function register(Registry $registry): void
    {
        $registry->settingsSection(new SettingsSection(
            name: 'newsletter',
            title: 'Newsletter',
            category: SettingsCategory::Integration,
            form: new Form([
                new Field('list_id', 'List'),
                Field::password('api_key', 'API key'),
            ]),
        ));

        $registry->handler('connect-callback', ConnectCallback::class);
        $registry->webhook('unsubscribe', UnsubscribeWebhook::class);
        $registry->diagnostic('connection', ConnectionCheck::class);
        $registry->schedule('resync', Resync::class, Frequency::everyMinutes(60));
    }
}
```

## Extension points

| Registry method | Adds |
|---|---|
| `settingsSection()` | a Site Settings section described as data, with its operations, contributions, save listeners and row hydrators |
| `fieldType()` | a field type any section can use, named `<handle>:<name>` (`mailchimp:optin`) so it never clashes; implementing `ImportableFieldTypeInterface` lets a CSV import fill it |
| `icons()` | a folder of SVG icons |
| `listen()` | a listener for an event, which a site binds to its sections in admin.xml; it may narrow the listener's field scope but never widen it |
| `diagnostic()` | a check in the update check, whose result can offer a one-click fix or a guided fix |
| `handler()` | an admin request handler at `/admin/extension/<handle>/<name>`, behind the ACL resource `extension.<handle>.<name>`, needing the `Handler\Privilege` it declares, or else View to read and Update for anything else |
| `webhook()` | a public handler at `/api/extension/<handle>/<name>`, which verifies its caller's signature or token before it runs |
| `migrations()` | tables of the add-on's own, `ext_<handle>_*`, on the site's database or the CMS's |
| `schedule()` | recurring work for the host's job runner, every so many minutes or on a cron expression |
| `script()` | scripts for public pages, each with its placement and consent category |
| `panel()` | a panel beside one record on its view and edit screens (its section, id and values in, a title, text and links out), shown on the sections a site binds it to; the host escapes everything in it |
| `module()`, `assets()`, `component()`, `templates()`, `menu()` | an admin area of the add-on's own, with its scripts, styles and dashboard components; only a module may ship templates or a section menu (see Modules) |
| `dependsOn()` | another add-on this one uses, through that add-on's `Event\` and `Api\` namespaces only |
| `provide()` | a service the host asks for by interface, such as the CDN purger core calls, made by a `Service\ServiceFactoryInterface` with the add-on's context, and only while the add-on is enabled |

A host reads everything declared back with `Registry::registration()`.

A handler or webhook can answer with a file to save, such as an export or an upload behind a signed link. The host
streams what the writer writes, as an attachment that isn't cached, so a large file is never held whole:

```php
return HandlerResult::download('entries.csv', 'text/csv', static function ($stream) use ($rows): void {
    foreach ($rows as $row) {
        fputcsv($stream, $row);
    }
});
```

`WebhookResponse::download()` takes the same arguments.

## Events

Add-ons react to what happens in the CMS through events named `<Type>Pre<Action>`, `<Type><Action>` and
`<Type>Post<Action>`, one guarantee per stage:

| Stage | When it runs | A listener may |
|---|---|---|
| `RecordPreSave` | before anything is written | change values, add a field error, warn, flag, or stop the save |
| `RecordSave` | inside the save's transaction | write its own `ext_<handle>_*` rows; if it throws, the whole save rolls back |
| `RecordPostSave` | after the transaction, in the background by default | anything outside the database; it runs whatever happened, and `succeeded()` says whether the save went through |

`Event\Catalogue::EVENTS` lists every event the CMS fires: the record actions (save, create, delete, duplicate,
status change, reorder, move, publish), settings saves, asset uploads and deletes, permission changes, sign-ins,
form entries, cache clears, migration runs, and `ExtensionPostFail`, fired when an add-on's code throws. Only the CMS
fires these; an add-on fires its own events, under its own `Event\` namespace, through its context's dispatcher.

An add-on that changes what the public site shows outside the records the CMS saves, such as publishing a menu,
fires an event of its own that implements `Event\SiteChangeEventInterface` once the change is made. The CMS clears
its caches, purges the CDN's copy of the site and sends the site's webhook after it, as after a record save:

```php
final readonly class MenuPostPublish extends AbstractPostEvent implements SiteChangeEventInterface
{
    public function __construct(
        public string $handle,
        Outcome $outcome = Outcome::Done,
        string $reason = '',
    ) {
        parent::__construct($outcome, $reason);
    }
}

$context->events()->dispatch(new MenuPostPublish($menu->handle));
```

A new record fires the Create events and then the Save events at each stage, so a listener to `RecordPostSave` hears
creations too. A chain of events fired from listeners may go `Event\ChainLimit::DEPTH` (8) deep, and an event can't
recur within its own chain.

When a Pre listener throws, the action stops with a plain message for everyone; the technical summary goes in the
result's `detail`, which hosts show only to administrators.

```php
$registry->listen(
    'sync-subscriber',
    RecordPostSave::class,
    SyncSubscriber::class,
    Scope::fields('email', 'name', 'newsletter'),
);
```

## Modules

A module is an add-on with an admin area of its own (plan section 10). `module()` names an admin.xml fragment
holding one area of categories and sections. A site's own admin.xml can still rename, reorder or hide it. An area or
a section names one of the module's handlers with `<handler>` to draw its screen:

```xml
<admin>
    <forms>
        <title>Forms</title>
        <icon>forms</icon>
        <handler>forms</handler>
        <category>
            <exports>
                <title>Exports</title>
                <section>
                    <scheduled>
                        <title>Scheduled exports</title>
                        <handler>exports</handler>
                    </scheduled>
                </section>
            </exports>
        </category>
    </forms>
</admin>
```

A module's handlers are guarded by its area's ACL resource (`forms` above), or by the section that names them
(`forms.exports.scheduled`), as the host's own screens are, so a role allowed the area keeps its screens.

A module can also add categories to an area that already exists, such as the CMS's Site Pages, by naming that area
and marking it `existing="true"`. The fragment then holds the module's own area, if it has one, and the areas it adds
to:

```xml
<admin>
    <site existing="true">
        <category>
            <navigation>
                <title>Navigation</title>
                <section>
                    <menus>
                        <title>Menus</title>
                        <handler>menus</handler>
                    </menus>
                </section>
            </navigation>
        </category>
    </site>
</admin>
```

A handler named in such a section is guarded by it (`site.navigation.menus`), as one in the module's own area is. A
module that adds to existing areas only has no area of its own, so a handler no section names is guarded by the first
section it adds, and a role allowed that section reaches all of the module's screens.

A handler answers with a described screen where it can, and with one of the module's templates where it can't
(decision M1). A described screen may carry a row of `Handler\Tab`s under its header, leading to the other screens
about the same record:

```php
return HandlerResult::template('fields', 'Fields', ['form' => $form]);
```

The host draws the dashboard around it, with the title in its header and breadcrumb. A screen or template that
belongs under another page, such as one record's, names it as its `parent` (a `Handler\ParentPage`, with a title and
one of the add-on's handlers), and the breadcrumb shows it between the area and the screen. For a script on one of the
module's screens, `HandlerResult::fragment('builder/row', $values)` draws a template alone, as HTML to put in place. The template is
`<templates>/fields.phtml` and runs with two variables: `$values`, as the handler passed them, and `$view`, a
`Module\TemplateHelpersInterface` for escaping, URLs to the module's handlers and assets, the form token, icons and
other templates. It uses the dashboard theme's markup and classes, never the host's own view helpers, so it draws
the same in any host. `Testing\InMemoryTemplates` draws a template on its own in a module's tests.

`menu()` names a `Module\MenuInterface` listing what the area's section menu shows beyond its admin.xml, such as the
forms in the form builder's tables. Each `Module\MenuItem` leads to one of the module's handlers, and may carry a
`count` the host shows beside its label. A module that only adds sections to an existing area has its menu drawn in
place of those sections, such as each of the site's menus in place of a single Menus section, while it lists anything
the person may view.

## Host context

Wherever an add-on's code runs, the host gives it an `ExtensionContextInterface`: its handle, the host's services,
a logger whose entries carry `source: extension.<handle>` (hosts can use `Log\SourceLogger` for that), a clock, the
site path, a URL resolver for records (`Url\UrlResolverInterface`, given a section and record id), an event
dispatcher for the add-on's own events, and its own settings:

```php
$settings = $context->settings();               // the section named after the handle
$secret   = $settings->get('secret', '');
$settings->set(['token' => $token]);            // saves as the settings screen would, firing the settings events
```

A host may also lend add-ons some of its own services from `services()`, named by interface or class, which an
add-on asks `has()` for first: the site's mail (`Mail\MailerInterface`) and its storage (contenir/contenir-storage's
`StorageManager`, whose profiles include `Storage\StorageProfile::PRIVATE`, which no URL reaches). In tests,
`Testing\MemoryMailer` and `Testing\MemoryStorage::manager()` stand in for them.

A host may also lend add-ons the site's own settings, read-only, from `services()` as
`Settings\SiteSettingsInterface`, which an add-on asks `has()` for first: `get('contact.telephone')` reads a setting
by its dotted key as text. `Testing\MemorySiteSettings` holds them in memory for tests.

A host may also lend add-ons who is signed in from `services()` as `Editor\CurrentEditorInterface`, which an add-on
asks `has()` for first: `current()` is the `Editor\Editor`, with their user id and name, for the admin request being
answered, and null when no one is signed in, as in a public webhook, a scheduled task or a background listener.

A host may also lend add-ons the site's records from `services()` as `Record\RecordsInterface`, which an add-on asks
`has()` for first, for add-ons that link to them, such as a menu's items. Only the sections the site marks as
linkable are offered, each a `Record\LinkableSection` naming its records ("Page", "News article"), whether new ones
can be made there, and the `Record\Workflow`s they can be made with:

```php
$records = $context->services()->get(RecordsInterface::class);

$found   = $records->search('team', limit: 10);                       // list<RecordSummary>: title, status, path, edit URL
$summary = $records->describe(new RecordReference('page', 12));       // null when it isn't there or isn't linkable
$free    = $records->isPathFree('/about/our-team');

$result = $records->create(new NewRecord('page', 'Our team', '/about/our-team', workflow: 'standard'));
```

`create()` makes a draft through the host's normal save path, so its validation, the extension events, holds and
the editor's permission to add to the section all apply, and it answers with the save's `Event\ActionResult`: Done
with the new id, or Stopped with field errors such as a path in use under `path`. When the add-on has a transaction
open on the site's database, the record is written inside it and rolls back with it, so a record and the row that
links to it are saved together or not at all.

The operation contracts the legacy CMS ships today (`Operation\Operation`, `Operation\Contribution`,
`Operation\OperationResult` and the context, handler, save-listener and row-hydrator interfaces) and
`IconPathProviderInterface` live here too, with the same shape, so the legacy CMS can alias its old names to them for
one release. An operation on an add-on's settings section gets that add-on's context from
`OperationContextInterface::extension()`, for its settings, and its logger from `logger()`; a core section's operation
gets null.

### Services

An add-on can provide a service that the CMS, or another add-on, asks the host for by interface. The interface lives in
a library both can require, so neither names the other:

```php
$registry->provide(PurgerInterface::class, PurgerFactory::class);

final class PurgerFactory implements ServiceFactoryInterface
{
    public function create(ExtensionContextInterface $context): PurgerInterface
    {
        return new CloudflarePurger(/* built from $context->settings(), logging to $context->logger() */);
    }
}
```

The host makes the service once, with the add-on's context, and only while the add-on is enabled, so disabling the
add-on takes the service away. When several enabled add-ons provide one interface, the first to load provides it and
the host logs the others. A factory that throws, or makes something that isn't the interface, is reported like any
add-on failure (`FailurePoint::Service`), and the host has no service.

## Conformance suite

A host proves it implements the API by running the conformance suite: abstract PHPUnit test cases in
`Contenir\Extension\Api\Conformance\`, each covering one part of the plan.

| Test case | Proves |
|---|---|
| `AbstractLoadingConformanceTestCase` | only enabled add-ons load, each once; one that fails to register is skipped and reported, and the rest still load |
| `AbstractStagesConformanceTestCase` | the three stages of a save: Pre changes values, stops or holds a save; the main stage rolls back on failure; Post runs after, whatever happened, in the background by default |
| `AbstractBindingConformanceTestCase` | listeners run only where a site binds them, within their field scope, in the site's order |
| `AbstractContextConformanceTestCase` | an add-on's context names it, stamps its log entries, and refuses core events and recurring chains |
| `AbstractHandlerConformanceTestCase` | admin handlers behind their ACL resource; public webhooks only once they verify, with GET and POST; downloads from both, as attachments |
| `AbstractModuleConformanceTestCase` | a module's handlers answer with its templates, values escaped, behind its area's or section's resource and the privilege each declares, including a section it adds to an existing area |
| `AbstractSettingsConformanceTestCase` | an add-on reads back what it saved to its settings, unsaved fields have their declared default, and writes fire the settings events; operations on its section run with its context, behind their privilege on `settings.<category>.<section>` |
| `AbstractRecordsConformanceTestCase` | the records lent to add-ons: only linkable sections are listed, searched, described and created in; a new record is a draft saved through the save path, which listeners can stop or fail and fields can hold, and needs the editor's permission to add |
| `AbstractEditorConformanceTestCase` | the editor lent to add-ons: the one signed in for the request being answered, and no one once it is over or when no one signed in |
| `AbstractServiceConformanceTestCase` | services add-ons provide: made once with the add-on's context, only while it is enabled, from the first to load; a failing factory is reported and provides nothing |
| `AbstractDiagnosticConformanceTestCase` | checks in the update check; fixes need `extension.<handle>.fix` and re-run the check |

A host's test suite extends each one and implements `createHost()`, returning a fresh
`Conformance\HostDriverInterface`: a small adapter that boots the host with the suite's probe add-on, saves records,
binds listeners, answers requests and runs checks the way the CMS itself would.

```php
final class LegacyStagesTest extends AbstractStagesConformanceTestCase
{
    protected function createHost(): HostDriverInterface
    {
        return new LegacyHostDriver(/* a fresh test site */);
    }
}
```

The suite needs PHPUnit 11, which a host already has in its dev dependencies.

## In-memory host

`Testing\InMemoryHost` is a host with no CMS behind it, holding records, bindings and logs in memory. It passes the
conformance suite, so add-ons can be tested against the API without either CMS running:

```php
$http = new Psr17Factory();
$host = new InMemoryHost($http, $http, new FrozenClock(new DateTimeImmutable('2026-10-19 09:00')));
$host->boot(['newsletter' => new NewsletterExtension()], ['newsletter'], [ListClient::class => $fakeClient]);
$host->bind('member', 'newsletter', 'sync-subscriber', 'RecordPostSave', ['email']);

$host->save('member', 1, ['email' => 'ann@example.com']);
$host->runBackground();
```

`handle()` takes the editor signed in for a request, if any, whom it lends add-ons as `Editor\CurrentEditorInterface`
while the request is answered. `linkable()` marks a section as linkable for the records it lends add-ons. A record's title, path and status are
its `title`, `path` and `status` values, and each linkable section is its own ACL resource. While it answers a request
the records act for an editor holding the request's grants; outside one, as when a test calls an add-on's classes with
`context()`, for an editor allowed everything.

`menu()` lists what a module's section menu shows for a request. It takes any PSR-17 factories for the requests
and responses handlers see; `Testing\FrozenClock`,
`Testing\MemoryLogger`, `Testing\ServiceMap` and `Testing\MapUrlResolver` are there for tests too.

## The boundary

An add-on may use PHP itself, this API, the PSR interfaces above, its own code, the libraries its composer.json
requires, and only the `Event\` and `Api\` namespaces of other add-ons it requires (plan section 12, P7). It may
not require either CMS, ZF1, Laminas MVC or Mezzio, and its code may not use this package's test tooling.

`vendor/bin/extension-boundary` writes those rules from the add-on's composer.json to `mago-boundary.toml`, which
the add-on's `mago.toml` extends, so `mago guard` enforces them in CI:

```toml
extends = ["vendor/contenir/contenir-qa-tools/mago.toml", "mago-boundary.toml"]
```

```bash
vendor/bin/extension-boundary
```

`--check` says whether the file is current without writing it. An add-on's test suite extends
`Conformance\AbstractAddOnConformanceTestCase` once, which checks that the add-on registers cleanly, that its
boundary file is current, and that every public webhook refuses a request it cannot verify (P1).

`tests/Fixture/Example` is a complete example add-on: a settings section with a masked secret, a listener, a signed
webhook and a check, with its boundary file, tests and CI workflow.

## Status

This package is phase 3 of the Contenir extensions plan. It is not tagged yet: hosts and add-ons follow `0.1.x-dev`
until it is.

## Licence

BSD-3-Clause. See [LICENSE.md](LICENSE.md).
