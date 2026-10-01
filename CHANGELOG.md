# Changelog

All notable changes to the Dixlase Pages plugin are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this plugin follows Semantic Versioning.

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
