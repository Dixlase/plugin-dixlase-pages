# Spin-off task — ship prebuilt front-end assets in the release ZIP (Dixlase build-pipeline extension)

> **Copy this into each affected extension repo** (its own `.backlog/` or a GitHub issue). It is self-contained — the extension-repo dev does not need the core session's context.

**For:** an official Dixlase **plugin or theme that has a front-end build** — i.e. it has a `vite.config.js`, and/or a `package.json` with a `build` script, and/or a gitignored `resources/assets` build output. **Extensions with no front-end assets: no action.**
**Applies now to:** `Dixlase/plugin-dixlase-seo`, `Dixlase/theme-dixlase-onepage` (and any future extension that adds a front-end build).
**Master design (why):** core backlog `extension-update-prebuilt-assets-no-npm.md`. Production installs have **no Node / npm**; core updates already pull **prebuilt** assets from the release ZIP. Extensions must do the same so admin-panel updates never need to build on the server.

---

## Tasks (extension repo side)

### 1. Standardize the build setup — `vite.config.js` + `package.json` together
- The extension must ship **both** `vite.config.js` **and** `package.json` (with a `"build"` script) so the assets are buildable locally and in CI.
- Add whichever is missing in your repo: **Inquiry / Menus / Pages** currently have `vite.config.js` but **no `package.json`** → add `package.json`. **SEO** has `package.json` but **no `vite.config.js`** → add `vite.config.js`. (Only the OnePage theme currently ships both.)
- `resources/assets` (the Vite build output) stays **gitignored** — it is a build artifact, not committed to the repo.

### 2. Bundle the built assets in the release ZIP — `.github/workflows/deploy.yml`
- In the release workflow, after `npm ci && npm run build`, **include the built `resources/assets/` in the packaged release ZIP**.
- ⚠️ Because `resources/assets` is gitignored, a plain `git archive` will **not** include it. The workflow must explicitly add the freshly built directory to the artifact (build into the checkout, then zip the extension dir **including** `resources/assets`).
- Match the ZIP layout core's extension updater expects (extension root containing `resources/assets`, etc.) — mirror how core bundles `public/assets/build` in its release.

### 3. Verify
- Download the produced release ZIP and confirm `resources/assets/` (with the built css/js) is inside.
- (Once core defaults extension updates to `auto`) an admin-panel update on a box **without npm** applies the prebuilt assets with **no build step**.

## Acceptance criteria
- [ ] Extension ships `vite.config.js` **and** `package.json` (with a build script) together.
- [ ] The **release ZIP contains the built `resources/assets/`**.
- [ ] After an update, assets work **without** the server running `npm`.

## Do NOT (ordering trap)
- Do **not** add a `package.json` to an extension that has **no** front-end build yet. Under the current core logic, a present `package.json` triggers an npm build on update. Universal `package.json` standardization is a **separate, core-ordered** step (only after core's extension-update default becomes `auto`). See the master doc.
