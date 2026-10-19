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
- The legacy CMS's operation contracts and `IconPathProviderInterface`, moved here unchanged in shape so the CMS can
  alias its old names to them for one release.
