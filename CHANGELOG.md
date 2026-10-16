# Changelog

All notable changes to the Dixlase Pages plugin are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this plugin follows Semantic Versioning.

## [Unreleased]

### Changed

- The page editor is built on core's shared content editor (#62): the editor type cards, the
  Markdown / HTML field with "Insert image", the CSS / JS tabs and the live preview come from core,
  and the plugin keeps the page's own state (slug, parent, status, storage). Every preview render
  goes through the page renderer. Requires Dixlase 0.1.13.

## [0.2.2] — 2026-10-16

### Security

- Creating, updating, moving to the trash, restoring from the trash and permanently
  deleting a page now each write an audit log entry with the actor and the page
  (fixes #59). Emptying the trash writes one entry per page. Who may do each of these
  is unchanged.

### Fixed

- Restoring a revision of a page stored as a file now rewrites its files. Before, only
  the database copy changed, so the public page and the edit screen kept showing the
  file as it was (fixes #58).

### Development

- A self-audit test replays the admin plugin audit's own checks: it fails when a
  permission declared in `plugin.json` does not match the code, or when a view breaks
  the CSP strict mode (#64, fixes #63).

## [0.2.1] — 2026-10-13

### Fixed

- Opening `/admin/pages/{page}` directly no longer fails with a server error; it
  redirects to the page's edit screen (#53, fixes #52).
- Re-signed. Changes merged after 0.2.0 had not been signed, so sites reported the
  plugin as `signature_invalid`.

### Development

- A smoke test opens every admin GET route of the plugin as the admin and the editor
  role and checks that none returns a server error (#54, fixes #51).

## [0.2.0] — 2026-10-12

### Added

- The page edit screen shows DixlaseMultilingual's "Translate" button below the page
  meta, linking to the translation screen for the page. It appears only when that
  plugin provides the button and the page has been saved (#50, fixes #49).

## [0.1.3] — 2026-10-11

### Fixed

- hreflang alternate URLs are now built from the page's own route, so a page whose
  slug differs between locales gets the correct URL for each locale (#48).
- Re-signed. The signature no longer matched the tree, so sites reported the plugin
  as `signature_invalid` and deducted from its health score. Changes had reached
  `main` through pull-request merges, which do not run the local signing hook.

### Changed

- Built front-end assets are no longer tracked in git; the release build produces
  them (#38).
- CI and dependency maintenance: `source-map-js` 1.2.1 → 1.2.2 (#41), the dead
  `COMPOSER_AUTH` block removed (#43), the checkout token no longer passed as a
  secret (#44), the release check now reads `manifest.json` instead of a fixed
  `app.js` (#40), and the deploy also covers the documentation site (#46).

## [0.1.2] — 2026-10-01

### Security

- Raw HTML inside a Markdown page is now shown as text instead of being rendered (#36).
  Markdown is open to editors below ADMIN, and writing HTML is reserved for ADMIN and
  above, but `<img onerror>`, `<form>`, `<meta http-equiv>` and similar tags passed through.
  This applies to the published page, the preview and the live preview, which now renders
  Markdown on the server so it matches the published page. Requires Dixlase core 0.1.3;
  on an older core, Markdown renders as before.

### Changed

- A Markdown page that relied on raw HTML now shows that HTML as text. Move such content
  to an HTML page (ADMIN and above). The bundled sample pages are not affected.

## [0.1.1] — 2026-10-01

### Changed

- Build tooling: `vite` 5 → 8.3.1, with `esbuild` and `postcss` 8.5.28 updated
  alongside (#35). This clears the Dependabot advisories for those packages,
  all of which affect only the development server and the asset build — nothing in
  them is shipped to sites. The prebuilt assets in the release ZIP are produced by
  the same build as before; only their hashed file names change.

## [0.1.0] — 2026-10-01
Initial release. Requires Dixlase `^0.1.0` (Plugin API `^0.1`), PHP `>= 8.3`.

### Added

- Static page management with an admin UI — create, edit, and publish pages.
- Front-end routes to render published pages.
- Page revision history with a recorded author (a revision per save), using the
  core `RevisionService` / `RevisionDiffPresenter`.
- SEO meta integration (`seo-meta` capability): per-page meta description and OGP
  image when an SEO plugin implementing `SeoMetaProviderInterface` is installed.
- Exposes pages as link targets for the Menus plugin and other consumers
  (`linkable` capability).
- Implements the `RouteSlugProvider` contract for configurable page URL slugs.
