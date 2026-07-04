# スピンオフ・タスク — リリース ZIP にビルド済み front-end アセットを同梱する（Dixlase ビルドパイプライン拡張）

> **該当する各拡張 repo にコピーして配る**（その repo の `.backlog/` か GitHub issue へ）。自己完結しており、拡張 repo 側の開発者はコアセッションの文脈を知らなくてよい。

**対象：** front-end ビルドを持つ公式 Dixlase **プラグイン/テーマ** — すなわち `vite.config.js` を持つ／`build` スクリプト付き `package.json` を持つ／gitignore な `resources/assets` ビルド出力を持つもの。**front-end アセットが無い拡張は対応不要。**
**現時点の対象：** `Dixlase/plugin-dixlase-seo`、`Dixlase/theme-dixlase-onepage`（および今後 front-end ビルドを足す拡張）。
**マスター設計（理由）：** コアバックログ `extension-update-prebuilt-assets-no-npm.ja.md`。本番には **Node / npm が無い**。コア更新は既にリリース ZIP の**プリビルド**アセットを引く。拡張も同様にし、管理画面更新がサーバー上でビルドを必要としないようにする。

英語版は [SPINOFF-extension-prebuilt-assets-release.md](./SPINOFF-extension-prebuilt-assets-release.md)。齟齬時は日本語版優先。

---

## タスク（拡張 repo 側）

### 1. ビルド構成を統一 — `vite.config.js` ＋ `package.json` をセットで
- 拡張は `vite.config.js` **と** `package.json`（`"build"` スクリプト付き）を**両方**同梱し、ローカル・CI でビルド可能にする。
- 自分の repo で欠けている方を追加：**Inquiry / Menus / Pages** は `vite.config.js` が有るのに **`package.json` が無い** → `package.json` を追加。**SEO** は `package.json` が有るのに **`vite.config.js` が無い** → `vite.config.js` を追加。（両方揃っているのは現状 OnePage テーマのみ。）
- `resources/assets`（Vite ビルド出力）は **gitignore のまま**（ビルド成果物、repo にはコミットしない）。

### 2. リリース ZIP にビルド済みアセットを同梱 — `.github/workflows/deploy.yml`
- リリースワークフローで `npm ci && npm run build` の後、**ビルド済み `resources/assets/` をパッケージするリリース ZIP に含める**。
- ⚠️ `resources/assets` は gitignore なので、素の `git archive` では**含まれない**。ワークフローが、ビルドしたディレクトリを成果物に明示的に加える必要がある（チェックアウト内でビルド→`resources/assets` を**含めて**拡張 dir を zip 化）。
- コアの拡張 updater が期待する ZIP レイアウトに合わせる（拡張ルート直下に `resources/assets` 等）。コアがリリースで `public/assets/build` を同梱するのと同じ考え方。

### 3. 検証
- 生成したリリース ZIP を落とし、`resources/assets/`（ビルド済み css/js）が中に在ることを確認。
- （コアが拡張更新の既定を `auto` にした後）**npm の無い**マシンでの管理画面更新が、**ビルド工程なし**でプリビルドを適用することを確認。

## 受け入れ条件
- [ ] 拡張が `vite.config.js` **と** `package.json`（build スクリプト付き）をセットで同梱。
- [ ] **リリース ZIP にビルド済み `resources/assets/` が含まれる**。
- [ ] 更新後、**サーバーで npm を回さずに**アセットが動く。

## やってはいけないこと（順序の罠）
- **まだ front-end ビルドを持たない**拡張に `package.json` を追加しないこと。現コアロジックでは package.json が在るだけで更新時に npm ビルドが走る。package.json の横断標準化は**別・コア主導**の工程（コアの拡張更新既定が `auto` になった後のみ）。マスター文書参照。
