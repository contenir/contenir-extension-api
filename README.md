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

## Status

This package is phase 3 of the Contenir extensions plan. It is not tagged yet: hosts and add-ons follow `0.1.x-dev`
until it is.

## Licence

BSD-3-Clause. See [LICENSE.md](LICENSE.md).
