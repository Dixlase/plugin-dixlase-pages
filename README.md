# Dixlase Pages

For Japanese, see [README.ja.md](./README.ja.md).

Static-page management for Dixlase: a hierarchical page tree under a configurable URL prefix (`/page/parent/child` by default), GUI / Markdown / HTML / Blade editors, database or file storage, per-page custom CSS / JavaScript, a revisions history with restore, draft / published / scheduled statuses, SEO-meta integration with DixlaseSEO, and internal-link interoperability with DixlaseMenu.

## Features

- **Hierarchical pages** — Up to three levels (`/page/parent/child`). Children are promoted to top-level when a parent is deleted.
- **Editors** — GUI / Markdown / HTML, switchable per page.
- **Storage** — Choose database or file storage per page.
- **Per-page custom CSS / JS** — Each page ships its own stylesheet and script.
- **Revisions** — Snapshots on every save, restorable anytime.
- **Publish workflow** — Draft / Published / Scheduled.
- **Configurable URL prefix** — Change the default `/page/` prefix from admin settings.
- **Role-based permission** — Per-menu role permissions.
- **Integrations** — SEO meta via DixlaseSEO, internal-link references via DixlaseMenu.

## Installation

Open the admin panel under **Dashboard → Plugins**, find this plugin, then download and enable it. The plugin's tables are created automatically on enable.

## Usage

Once enabled, **Pages** appears in the admin sidebar with list / new / edit / settings screens.

Public URLs are composed from the prefix and the page's path. Example: an `about` page becomes `/page/about`, and its child `team` becomes `/page/about/team`.

## Capabilities

This plugin declares the following capabilities in `plugin.json`:

- **`seo-meta`** — Contract for DixlaseSEO to read and write per-page SEO metadata (meta description, OGP image).
- **`linkable`** — Contract for plugins like DixlaseMenu to reference pages by ID. The full URL (hierarchy + locale prefix) is composed on the Pages side.
- **`multilingual-content`** — Reserved capability key for future multilingual support. Activates once the multilingual runtime ships as a separate plugin.

## License

Dixlase Pages is distributed under a **dual license**:

- **Open Source License**: [GNU General Public License v3](./LICENSE)
- **Commercial License**: A separate commercial license is planned for use cases where GPL v3 compliance is not feasible. **It is not yet available** — only a draft of the eventual terms is present in [LICENSE-COMMERCIAL](./LICENSE-COMMERCIAL). For availability timing or other questions, contact **info@dixlase.org**.

A short overview of how these files fit together is in [NOTICE](./NOTICE) ([日本語](./NOTICE.ja)).

## Contributing

We do not yet accept external code Pull Requests. They will open once we have assessed core API stability and how the project operates after the initial release, and prepared a Contributor License Agreement (CLA) that has passed legal review. Once the CLA is finalized, contributions will fall under the [Dixlase Copyright Policy](https://github.com/Dixlase/dixlase-core/blob/main/COPYRIGHT-POLICY.md) and the Dixlase CLA (see CONTRIBUTING.md). Bug reports and proposals via Issues are welcome.

---

© 2026 exc-D inc. and Dixlase contributors
