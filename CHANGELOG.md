# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased] - 2026-10-19

### Added

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
