# 変更履歴

Dixlase Pages プラグインの主要な変更はすべてこのファイルに記録します。

フォーマットは [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) に準拠し、
本プラグインはセマンティックバージョニングに従います。

## [0.1.0] — YYYY-MM-DD

初回リリース。Dixlase `^0.1.0`（Plugin API `^0.1`）、PHP `>= 8.3` が必要です。

### 追加

- 管理 UI による固定ページ管理 — ページの作成・編集・公開。
- 公開済みページを表示するフロントエンドルート。
- 記録された著者付きのページリビジョン履歴（保存ごとに 1 リビジョン）。コアの
  `RevisionService` / `RevisionDiffPresenter` を利用。
- SEO メタ連携（`seo-meta` capability）: `SeoMetaProviderInterface` を実装した
  SEO プラグインが導入されている場合に、ページ単位のメタディスクリプションと
  OGP 画像を提供。
- Menus プラグインなどの利用者向けに、ページをリンク先として公開
  （`linkable` capability）。
- 多言語ページコンテンツ（`multilingual-content` capability）。
- 設定可能なページ URL スラッグのために `RouteSlugProvider` 契約を実装。
