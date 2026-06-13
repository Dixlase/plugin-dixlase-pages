# Dixlase Pages

For English, see [README.md](./README.md).

Dixlase 用の固定ページ管理プラグイン。

- 設定可能な URL プレフィックス配下に階層的なページツリー
- GUI / Markdown / HTML の 3 種類のエディタ、データベース またはファイルでの保存
- ページごとのカスタム CSS / JavaScript
- 復元可能なリビジョン履歴
- 下書き / 公開 / 予約公開のステータス
- DixlaseSEO 連携による SEO メタ管理
- DixlaseMenu からの内部リンク参照に対応

## 機能

- **階層ページ** — 最大 3 階層 (`/page/parent/child`)。親削除時は子をトップレベルへ昇格。
- **エディタ** — GUI / Markdown / HTML を切り替え可能。
- **ストレージ** — ページごとに DB / ファイルから選択。
- **カスタム CSS / JS** — ページごとに独自のスタイルとスクリプトを設定可能。
- **リビジョン** — 保存ごとに自動記録、いつでも復元可能。
- **公開ワークフロー** — 下書き / 公開 / 予約公開を切り替え可能。
- **URL プレフィックス設定** — 既定 `/page/` を変更可能。
- **ロール権限** — 管理メニューごとにロール権限を設定可能。
- **連携** — DixlaseSEO で SEO メタ、DixlaseMenu で内部リンク参照に対応。

## インストール

管理画面の **ダッシュボード → プラグイン** から本プラグインを検索し、ダウンロード → 有効化します。有効化すると本プラグイン用のテーブルが自動で作成されます。

## 使い方

有効化すると管理画面のサイドバーに **ページ管理** が追加され、一覧 / 新規 / 編集 / 設定の各画面でページを操作できます。

公開 URL はプレフィックス + ページのパスで構成されます。例: `about` のページは `/page/about`、その下の `team` は `/page/about/team`。

## Capabilities

本プラグインは `plugin.json` で以下の capability を宣言しています。

- **`seo-meta`** — DixlaseSEO から SEO メタ(meta description / OGP 画像)を読み書きするための契約。
- **`linkable`** — DixlaseMenu などからページを ID 参照するための契約。階層パスと locale プレフィックス込みで URL を自動構築します。
- **`multilingual-content`** — 将来の多言語対応に向けた予約 capability キー。対応する多言語ランタイムがリリースされた時点で稼働します。

## ライセンス

Dixlase Pages は **デュアルライセンス** で配布されています。

- **オープンソースライセンス**: [GNU General Public License v3](./LICENSE)
- **商用ライセンス**: GPL v3 の遵守が現実的でないユースケース向けに、別途商用ライセンスの提供を予定しています。

**現時点では商用ライセンスはまだ提供しておりません。**  
(雛形のみ [LICENSE-COMMERCIAL](./LICENSE-COMMERCIAL) に Draft として置いています)。  
提供開始時期や条件に関するお問い合わせは **info@dixlase.org** までご連絡ください。

各ファイルの関係概要は [NOTICE.ja](./NOTICE.ja)([English](./NOTICE))にあります。

## コントリビューションについて

CLA (Contributor License Agreement) のレビュー中のため、現在 Pull Request を受け付けていません。  
CLA 確定後に受付を開始し、その時点から [Dixlase Copyright Policy](https://github.com/Dixlase/dixlase-core/blob/main/COPYRIGHT-POLICY.md) と Dixlase CLA(詳細は CONTRIBUTING.md)の対象となります。  
それまでも Issue での不具合報告・機能提案は歓迎しています。

---

(C) exc-D inc.
