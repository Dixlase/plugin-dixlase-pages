# Changelog

All notable changes to the Dixlase Pages plugin are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this plugin follows Semantic Versioning.

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
