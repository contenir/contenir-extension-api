# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased] - 2026-10-21

### Added

- Tabs on template screens: `Template::$tabs`, given as `HandlerResult::template($name, $title, $values, $tabs)`, and
  `TemplateHelpersInterface::tabs()`, which draws them as the host draws a described screen's for the template to
  print under its own header. A module's template screens (a form's fields editor or preview) share a row of tabs
  with its described ones. A template without tabs draws an empty string. `InMemoryTemplates` draws them as a list
  of links with the current one marked.
- `AbstractModuleConformanceTestCase` checks a template's tabs are drawn where it prints them, with the current one
  marked.
- Services (P13): `Registry::provide($interface, $factory)` declares a service the host asks for by interface, such as
  the CDN purger core calls. A `Service\ServiceFactoryInterface` makes it with the add-on's context, once, and only
  while the add-on is enabled. When several enabled add-ons provide one interface, the first to load provides it and
  the host logs the others. `Registration::$services` lists them, and `FailurePoint::Service` names a factory that
  failed.
- `OperationContextInterface::extension()`: the context of the add-on whose settings section declared the operation,
  so its operations can read its settings; null for a core section's.
- `HostDriverInterface::operate()` and `provided()`, with `Conformance\AbstractServiceConformanceTestCase` and
  operation tests in `AbstractSettingsConformanceTestCase`. The in-memory host runs settings operations and provides
  services. The probe add-on gains two operations, three services and a rival add-on (`Probe\RivalExtension`).
- Mail (phase 9, for the form builder's Preview): `Mail\MailerInterface` sends a `Mail\Message` (its recipients,
  subject, HTML and text, and optionally who it is from and who replies go to) the way the site sends its own mail.
  A host that sends mail offers it from each add-on's `services()`, named by the interface; an add-on asks `has()`
  first. `Exception\MailFailedException` says a message couldn't be sent, and `Testing\MemoryMailer` keeps what it
  is sent, or fails every message.

### Changed

- `OperationContextInterface` and `HostDriverInterface` each gain methods, so a host implementing them must add them.
- `TemplateHelpersInterface` gains `tabs()`, so a host implementing it must add it.

## [0.5.0] - 2026-10-20

### Added

- Fragments: `HandlerResult::fragment($name, $values, $status)` answers with one of a module's templates drawn
  alone, as HTML for a script on one of its screens to put in place, such as a new row in an editor or the body of
  a dialog. It is `ResultKind::Fragment`, holding a `Handler\Fragment`, and the template runs as a screen
  template's does. A handler of an add-on that is not a module fails if it answers with one.
- `AbstractModuleConformanceTestCase` checks a fragment is drawn alone, with its status.

## [0.4.0] - 2026-10-20

### Added

- Tabs on described screens: `Screen::$tabs`, a list of `Handler\Tab`s (a label, one of the add-on's handlers, a
  query, whether it is current, and an icon). The host draws them under the page header, as the theme's underline
  tabs, so the screens about one record (a form's entries, fields and settings) share a row of them. A screen
  without tabs is drawn as before.

## [0.3.0] - 2026-10-20

### Added

- Module templates (decision M1): `HandlerResult::template($name, $title, $values)` answers with one of a module's
  templates, as `ResultKind::Template` holding a `Handler\Template`. The host draws the dashboard around it with the
  title in its header. The template runs with `$values` and `$view`, a `Module\TemplateHelpersInterface` (escape,
  handler and asset URLs, the form token, icons and other templates), so it never needs the host's view helpers. A
  handler of an add-on that is not a module fails if it answers with a template.
- `Registry::menu()`: a module's `Module\MenuInterface` lists what its section menu shows beyond its admin.xml, as
  `Module\MenuGroup`s of `Module\MenuItem`s leading to its handlers. `FailurePoint::Menu` names a menu that throws.
- `Handler\Privilege`: `Registry::handler()` takes the privilege a handler needs whatever the method, such as Delete,
  so a module keeps the permissions its screens had. Without one, a handler needs View to read and Update for
  anything else, as before. `HandlerDeclaration::privilegeFor()` says which a request needs.
- `Conformance\AbstractModuleConformanceTestCase` and its probe module. A grant given to
  `HostDriverInterface::handle()` may name one privilege as `<resource>:<privilege>`.
- `Testing\InMemoryTemplates` draws a module's templates in its tests, and `InMemoryHost::menu()` lists its menu.

### Changed

- A module's handlers are guarded by its area's ACL resource, or by the section whose `<handler>` names them in its
  admin.xml, instead of `extension.<handle>.<name>`, so a role allowed the area keeps its screens.
- The package requires the DOM extension, which the in-memory host uses to read a module's admin.xml.

## [0.2.0] - 2026-10-20

### Added

- `Registry::panel()`: a panel beside one record on its view and edit screens (P9). The panel class implements
  `Panel\RecordPanelInterface`, gets the record as a `Panel\RecordView` (section, id and stored values) and returns a
  `Panel\Panel` (a title, paragraphs of plain text and `Panel\Link`s), or null to show nothing. The host escapes all
  of it. A site binds a panel to the sections it shows on. `Registration::$panels` lists them, and
  `FailurePoint::Panel` names a panel that throws.
- `Field\ImportableFieldTypeInterface`, which a field type implements when a CSV import may fill it. Its
  `importCell()` turns a trimmed cell into the value an editor would submit, which the host then validates and
  normalises as usual, or throws `Exception\CellRejectedException` with a message for the editor. A field type
  without it is left out of imports, as before.

### Changed

- Public webhooks are mounted at `/api/extension/<handle>/<name>`, under the `/api` prefix sites send to the CMS,
  where they were at `/extension/<handle>/<name>`. The handler conformance case asks for the new path.

## [0.1.0] - 2026-10-19

### Added

- An add-on reads and writes its own settings through `ExtensionContextInterface::settings()`
  (`Settings\SettingsInterface`). Writing saves as the settings screen does, firing the settings events.
  `Exception\UnknownSettingException` is thrown for a section or field the add-on didn't declare.
- `Event\ActionResult`, what the host reports after an action (it replaces `Conformance\SaveOutcome`), with a
  `detail` for administrators beside the message everyone sees. `PreEventInterface::stop()` takes the detail too.
- An add-on's field types are named `<handle>:<name>` (`mailchimp:optin`), so they never clash with each other's or
  the built-in types; `Registry::fieldType()` takes the name and adds the handle.
- `Event\ChainLimit::DEPTH`, the depth a chain of events fired from listeners may reach (8).
- `Conformance\AbstractSettingsConformanceTestCase`, and conformance tests for creating records and for a failing
  Pre listener's message and detail.

- `ExtensionInterface`, an add-on's entry class, and `Registry`, the one door from an add-on into the CMS. The
  registry checks each declaration as it is made and hands the host everything declared as a `Registration`.
- `Handle`, an add-on's stable id, with the names it implies: the log source `extension.<handle>`, the ACL resources
  `extension.<handle>.<name>` and `extension.<handle>.fix`, and the table prefix `ext_<handle>_`.
- Neutral declarations: described forms (`Form\Form`, `Form\Field` with a masked, locally stored password field,
  `Form\Group`), settings sections (`Settings\SettingsSection`), field types (`Field\FieldTypeInterface`), checks
  with one-click and guided fixes (`Diagnostic\*`), admin handlers and public webhooks with described screens
  (`Handler\*`), migrations on the site or CMS database (`Migration\*`), schedules (`Schedule\*`), public-site
  scripts with a consent category (`Script\*`), module pieces (`Module\*`) and dependencies on other add-ons.
- `Context\ExtensionContextInterface`: the add-on's handle, services, logger, clock and site path, given to its code
  wherever it runs.
- The event catalogue (`Event\Catalogue`), with Pre, main and Post stages: records (save, create, delete, duplicate,
  status change, reorder, move and publish), settings saves, asset uploads and deletes, permission changes, sign-ins
  and refused sign-ins, form entries (submit, status change, redaction), cache clears, migration runs, and
  `ExtensionPostFail` for an add-on's failures. Pre events can change values, add field errors, warn, flag or stop;
  Post events carry the outcome.
- `Registry::listen()`, with a field scope a site's binding may narrow but not widen, and Post listeners in the
  background unless they ask to run immediately.
- `Log\SourceLogger`, which stamps an add-on's log entries with `extension.<handle>`, and `Url\UrlResolverInterface`
  with `Url\RecordReference`, for a record's public address.
- The context gives add-on code a URL resolver and a PSR-14 dispatcher for the add-on's own events.
- The conformance suite (`Conformance\Abstract*ConformanceTestCase`): abstract PHPUnit test cases a host extends,
  through a `Conformance\HostDriverInterface` adapter, to prove it loads add-ons, runs the three stages, honours
  bindings, gives add-ons their context, answers handlers and webhooks, and runs checks and fixes as the API says. They
  drive a probe add-on (`Conformance\Probe\*`) that records what it saw.
- `Testing\InMemoryHost`, a host held in memory that passes the conformance suite, for testing add-ons without a CMS,
  with `Testing\FrozenClock`, `Testing\MemoryLogger`, `Testing\ServiceMap` and `Testing\MapUrlResolver`.
- `Exception\DispatchRefusedException`, thrown when an add-on fires a core event, or an event that would recur in its
  chain or make the chain too deep.
- The add-on boundary (`Boundary\*`) and `vendor/bin/extension-boundary`, which writes an add-on's
  `mago-boundary.toml` from its composer.json for `mago guard` to enforce: the API, the PSR interfaces, its own code,
  the libraries it requires, and only the `Event\` and `Api\` namespaces of other add-ons. Requiring a CMS, ZF1,
  Laminas MVC or Mezzio is refused, and the API's test tooling stays out of add-on code.
- `Conformance\AbstractAddOnConformanceTestCase`, which an add-on extends to prove it registers cleanly, keeps its
  boundary file current, and has every public webhook refuse a request it cannot verify.
- An example add-on (`tests/Fixture/Example`) with its boundary, tests and CI workflow.
- The legacy CMS's operation contracts and `IconPathProviderInterface`, moved here unchanged in shape so the CMS can
  alias its old names to them for one release.
