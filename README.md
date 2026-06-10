# Dixlase Pages

For Japanese, see [README.ja.md](./README.ja.md).

Static-page management for Dixlase: a hierarchical page tree under a configurable URL prefix (`/page/parent/child` by default), GUI / Markdown / HTML / Blade editors, database or per-locale file storage, per-page custom CSS / JavaScript, a revisions history with restore, draft / published / scheduled statuses, SEO meta integration, and translation overlays via Dixlase Multilingual.

## Features

- **Hierarchical pages** — Up to three levels deep (`/page/a/b/c`). Choose a parent on the edit form; deleting a parent promotes its children to top-level rather than cascade-deleting them. Slug uniqueness is scoped per parent.
- **Four editor types** — GUI builder, Markdown, raw HTML, and Blade. Simple mode (admin default) restricts new pages to GUI / Markdown; Advanced mode unlocks HTML and Blade. Existing pages keep editing in whatever type they were created with.
- **Two storage backends** — Choose at creation time:
  - **Database** — content lives in the page row. Fastest path for short pages.
  - **File** — content is written to `storage/app/private/dixlase-pages/{slug}.{ext}` with locale-specific copies (`{slug}.{locale}.{ext}`). Easier to edit through a text editor or commit to Git for long-form pages.
- **Per-page custom CSS / JS** — Each page can ship its own stylesheet and script, served at `/page/{path}/custom-style.css` and `/page/{path}/custom-script.js` with cache headers and CSP exemptions handled automatically.
- **Revisions** — Every save records a snapshot. Manual and automatic revisions are listed in the admin and can be restored without overwriting the current state (a pre-restore backup is created).
- **Publish workflow** — Draft / Published / Scheduled (with future `published_at`). Drafts and future-scheduled pages are visible to logged-in admins (preview banner) and 404 to public visitors.
- **SEO meta** — When the `dixlase-seo` plugin is enabled, the edit form exposes meta-description and OGP-image fields; the values are stored by Dixlase SEO via the `seo-meta` capability contract.
- **Multilingual** — When `dixlase-multilingual` is enabled, the source language is stored on the page row's `lang` column, with per-locale translation overlays stored by Multilingual. Source language can be changed after creation.
- **Configurable URL prefix** — The route prefix (default `page`) is stored in admin settings and respected by both the public route and the admin URL preview.
- **Role-based permission** — Default permissions for the Pages admin menus are declared in `config/admin/roles.php` (index / create: Editor and above, settings: Admin only) and resolved at runtime by the core `PermissionRegistry`.
- **Internal-link provider** — Implements `RouteSlugProvider` and a Linkable provider so other plugins (e.g. menu, blog) can reference pages by ID and get the correctly composed `/page/parent/child` URL — including under a locale prefix.

## Installation

1. Place the plugin at `plugins/DixlasePages` inside your Dixlase installation.
2. Enable it from the admin panel under **Dashboard → Plugins**, or run the equivalent CLI install command for your environment.
3. After enable, the plugin's migrations run automatically and seed the default URL prefix (`page`), publishing role, and page-status options.

## Usage

After enable, **Dashboard → Pages** appears in the admin sidebar.

- **List view** lists every page with search / status filter and per-column sort.
- **New page** opens the editor split-view: content area on the left, settings on the right (storage type, slug, parent page, language, status, custom CSS / JS, SEO meta when available).
- **Edit** opens the same editor on an existing row. Live preview is available for HTML / Markdown / GUI editors.
- **Settings** (admin only) lets you change the URL prefix, default editor type, and minimum publish role.

Public URLs are composed from the prefix and the page's path segments: a top-level page named `about` becomes `/page/about`, and a child page `team` under `about` becomes `/page/about/team`. When the multilingual plugin's locale URL routing is enabled, the same route is mirrored under `/{locale}/page/...`.

## Capabilities

This plugin declares the following capability in `plugin.json`:

- **`multilingual-content`** — Reserved capability key for future multilingual content support. Declared on plugins that store user-editable text intended to be translatable once the supporting runtime is in place.

## License

Dixlase Pages is distributed under a **dual license**:

- **Open Source License**: [GNU General Public License v3](./LICENSE)
- **Commercial License**: For use cases where GPL v3 compliance is not feasible, a separate commercial license is available — see [LICENSE-COMMERCIAL](./LICENSE-COMMERCIAL) (currently a draft) or contact **info@dixlase.org**.

A short overview of how these files fit together is in [NOTICE](./NOTICE) ([日本語](./NOTICE.ja)).

Contributions to this plugin repository are governed by the [Dixlase Copyright Policy](https://github.com/Dixlase/dixlase-core/blob/main/COPYRIGHT-POLICY.md) and the Dixlase Contributor License Agreement (see CONTRIBUTING.md).

---
(C) exc-D inc. - 2026
