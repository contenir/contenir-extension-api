# contenir/extension-api

[![Continuous Integration](https://github.com/contenir/contenir-extension-api/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/contenir/contenir-extension-api/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/contenir/contenir-extension-api/graph/badge.svg)](https://codecov.io/gh/contenir/contenir-extension-api)

The add-on API for Contenir CMS. Add-ons build against this package and nothing else; each CMS implements it as a
host. So an add-on works the same whether the CMS underneath is the legacy ZF1 CMS or the Mezzio rewrite.

It holds only contracts and value objects, with no framework types: PSR-3 logging, PSR-7 requests, PSR-11 services,
PSR-14 events and PSR-20 time.

## Requirements

- PHP 8.3, 8.4 or 8.5

## An add-on

An add-on is a Composer package of type `contenir-extension`, named `contenir-extension/<handle>`, whose classes
live under `Contenir\Extension\<Package>\`. Its composer.json names its handle and its entry class:

```json
{
    "name": "contenir-extension/newsletter",
    "type": "contenir-extension",
    "require": {
        "contenir/extension-api": "^0.1"
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
| `fieldType()` | a field type any section can use |
| `icons()` | a folder of SVG icons |
| `listen()` | a listener for an event, which a site binds to its sections in admin.xml; it may narrow the listener's field scope but never widen it |
| `diagnostic()` | a check in the update check, whose result can offer a one-click fix or a guided fix |
| `handler()` | an admin request handler at `/admin/extension/<handle>/<name>`, behind the ACL resource `extension.<handle>.<name>` |
| `webhook()` | a public handler at `/extension/<handle>/<name>`, which verifies its caller's signature or token before it runs |
| `migrations()` | tables of the add-on's own, `ext_<handle>_*`, on the site's database or the CMS's |
| `schedule()` | recurring work for the host's job runner, every so many minutes or on a cron expression |
| `script()` | scripts for public pages, each with its placement and consent category |
| `module()`, `assets()`, `component()`, `templates()` | an admin area of the add-on's own, with its scripts, styles and dashboard components; only a module may ship templates |
| `dependsOn()` | another add-on this one uses, through that add-on's `Event\` and `Api\` namespaces only |

A host reads everything declared back with `Registry::registration()`.

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

```php
$registry->listen(
    'sync-subscriber',
    RecordPostSave::class,
    SyncSubscriber::class,
    Scope::fields('email', 'name', 'newsletter'),
);
```

## Host context

Wherever an add-on's code runs, the host gives it an `ExtensionContextInterface`: its handle, the host's services,
a logger whose entries carry `source: extension.<handle>` (hosts can use `Log\SourceLogger` for that), a clock, the
site path, a URL resolver for records (`Url\UrlResolverInterface`, given a section and record id), and an event
dispatcher for the add-on's own events.

The operation contracts the legacy CMS ships today (`Operation\Operation`, `Operation\Contribution`,
`Operation\OperationResult` and the context, handler, save-listener and row-hydrator interfaces) and
`IconPathProviderInterface` live here too, with the same shape, so the legacy CMS can alias its old names to them for
one release.

## Conformance suite

A host proves it implements the API by running the conformance suite: abstract PHPUnit test cases in
`Contenir\Extension\Api\Conformance\`, each covering one part of the plan.

| Test case | Proves |
|---|---|
| `AbstractLoadingConformanceTestCase` | only enabled add-ons load, each once; one that fails to register is skipped and reported, and the rest still load |
| `AbstractStagesConformanceTestCase` | the three stages of a save: Pre changes values, stops or holds a save; the main stage rolls back on failure; Post runs after, whatever happened, in the background by default |
| `AbstractBindingConformanceTestCase` | listeners run only where a site binds them, within their field scope, in the site's order |
| `AbstractContextConformanceTestCase` | an add-on's context names it, stamps its log entries, and refuses core events and recurring chains |
| `AbstractHandlerConformanceTestCase` | admin handlers behind their ACL resource; public webhooks only once they verify, with GET and POST |
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

It takes any PSR-17 factories for the requests and responses handlers see; `Testing\FrozenClock`,
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
