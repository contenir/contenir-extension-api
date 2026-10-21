# contenir-extension/example

The example add-on for Contenir CMS, and the starting point for a new one. It is built against
[contenir/contenir-extension-api](https://github.com/contenir/contenir-extension-api) alone and shows each part an
add-on usually has:

- a settings section, with a masked signing secret (`ExampleExtension`);
- a listener that logs a record's new title once its save has gone through (`NoteTitle`), which a site binds to
  its sections in admin.xml;
- a public webhook that answers pings signed with the secret, which it reads from its own settings, and refuses any
  it cannot verify (`Ping`);
- a check in the update check, failing with a guided fix until the secret is set (`SecretCheck`).

## The boundary

An add-on may use the extension API, the PSR interfaces it names, its own code, the libraries its composer.json
requires, and only the `Event\` and `Api\` namespaces of other add-ons it requires. `mago-boundary.toml` writes that
down for `mago guard`; `mago.toml` extends it, so CI fails on anything else. After changing dependencies, run:

```bash
vendor/bin/extension-boundary
```

`tests/ExampleExtensionTest.php` extends the API's `AbstractAddOnConformanceTestCase`, which checks the add-on
registers cleanly, the boundary file is current and every webhook refuses an unsigned request.

## CI

`.github/workflows/continuous-integration.yml` runs the shared Contenir workflow for add-ons: Mago (format, lint,
analysis and the boundary), PHPUnit, `composer audit`, and Infection on the lines a pull request changes, at 80%.
