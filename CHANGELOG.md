# Changelog

All notable changes to the Dixlase Pages plugin are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this plugin follows Semantic Versioning.

## [0.4.0] — 2026-11-01

### Added

- The page editor's right column is grouped into sections that open and close, with the core part
  the blog's editor uses (#114, fixes #113). Title and slug start open; SEO, page information,
  revisions and publish settings start closed and show a summary next to their title.
- On a page, the admin bar offers *Edit this page* to a signed-in member who may edit pages (#110,
  fixes #109). Visitors, members below the edit role and sites without the admin routes get no link.

### Changed

- **This release needs core 0.2.0.** The "publish on file save" mode takes effect only in a local
  environment (`APP_ENV=local`) (#103, fixes #102), and the plugin now asks core where it applies
  instead of checking for itself (#105, fixes #104). Anywhere else a page waits for **Publish**,
  whatever is selected, and the settings screen says which behaviour applies. `requires.dixlase` is
  `>=0.2.0`.
- The Translate button and the language field on the edit screen are reached through core's seams —
  `<x-admin.translate-button>` and `LocaleHelper::localeUrlRoutingEnabled()` — instead of another
  extension's names (#112, fixes #83). On a site whose translation extension does not implement
  those seams yet, the button and the field are not shown; update that extension first.
- Both READMEs open with the social preview image (#100, fixes #99).

### Development

- The tests can be run by hand against another core ref (#107, fixes #106).
- The file-content tests check what this plugin owes rather than core's take-in policy, which
  differs between core releases (#111, fixes #108).

## [0.3.0] — 2026-10-30

### Added

- A Markdown page carries its own CSS, as an HTML page does, so the classes it gives elements
  (`{.class}`) have something to style them (#92, fixes #91). The page links and serves
  `custom-style.css`; JavaScript stays HTML-only. Only members who may author scripts (ADMIN and
  above) set the CSS, and an editor who saves the page keeps the CSS an admin wrote. The editor
  shows the CSS tab for Markdown with core 0.1.20 or later; on an earlier core the tab stays
  HTML-only and nothing else changes.

### Fixed

- The live preview and the preview of an unsaved page keep working when an empty field arrives as
  `null` rather than `''` (#96, fixes #94). They returned an error before.

### Changed

- `requires.dixlase` states the oldest core this works on as a floor (`>=0.1.19`) rather than a
  caret, so a core 0.2 release does not refuse to install or update it (#101;
  Dixlase/dixlase-core#790).
- The plugin thumbnail is replaced with a version with an adjusted layout and colours (#98, fixes
  #97).

### Development

- Pull requests against `next`, the development branch, run the checks (#90, fixes #89).
- The release build compares the files with the signature manifest at the tag and stops before
  building the ZIP on a mismatch (#93; part of Dixlase/dixlase-core#520).
- The deploy workflow recognises a checkout of this repository by its identity rather than by the
  text of its URL, and a site it refuses no longer holds the update back from the others (#95; fixes
  Dixlase/dixlase-core#518 and #519).

## [0.2.5] — 2026-10-24

### Changed

- A page stored as a file is published through core's shared layer for file-stored content
  (#88, fixes #57). By default the site shows the copy last saved or published from the admin: a change made
  to the page's files outside the admin is recorded once in the audit log as a warning, and the
  edit screen shows what changed with a Publish button. Publishing is audited and recorded as a
  revision. The new "File-stored pages" setting can instead take each file change in at once,
  recorded in the audit log and as a revision. **Behaviour change:** on a site that edits page
  files outside the admin, those edits stop reaching the site after the update until they are
  published from the edit screen.
- The daily integrity scan also checks the pages' files, and the dashboard counts the changes
  waiting to be published.
- Requires Dixlase 0.1.19.
- Comments describe the translation integration through core (the `TranslationResolver` contract
  and the `localized-url.providers` tag) instead of naming the extension that provides it (#86,
  fixes #85). No behaviour changed.

### Development

- Bump `vite` from 8.3.1 to 8.3.3, a build-time dependency that is not shipped to sites (#87).

### Upgrade notes

This release adds a database migration (the hash columns on the pages table). Plugin migrations
are not run by `php artisan migrate`. Updating with `dls:plugin:update` runs it; a site that
receives the files another way runs `php artisan dls:plugin:migrate DixlasePages --force` once.

## [0.2.4] — 2026-10-18

### Fixed

- A member signed in to the admin can open a draft or a page scheduled for later on the front,
  with the preview banner and the page's custom CSS / JS (#73, fixes #66). Visitors still get
  404, and an unpublished page is sent with `Cache-Control: private, no-store`.
- Saving the plugin's settings leaves an entry in the audit log, with the changed keys and their
  old and new values (#72, fixes #69). A save that changes nothing writes no entry.
- `plugin.json` declares the contracts the plugin implements (`LinkableProviderInterface`,
  `LocalizedUrlProvider`, `Revisionable`) and its role configuration (#76, fixes #70; #79,
  fixes #78).

### Changed

- The settings screen and the page editor register their Alpine data instead of passing object
  literals in `x-data`, in preparation for Alpine's CSP build (#74, fixes #71; #77, fixes #75).
- The README, comments, tests, language files and this changelog no longer name plugins that are not distributed publicly. Each integration is
  described by the core mechanism it goes through (#68, fixes #67; #84, fixes #82). No behaviour changed.

### Development

- The settings audit test compares the recorded changes regardless of key order, so it also
  passes on MySQL 8 (#81, fixes #80).

## [0.2.3] — 2026-10-17

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
