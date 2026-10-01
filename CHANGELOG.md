# Changelog

All notable changes to the Dixlase Pages plugin are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this plugin follows Semantic Versioning.

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
