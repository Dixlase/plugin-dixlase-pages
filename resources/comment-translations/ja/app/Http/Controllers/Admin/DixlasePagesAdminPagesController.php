<?php

/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase Pages is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU General Public License version 3 or later, as published
 *       by the Free Software Foundation; or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the GPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

return [
    '/**
     * Display preview from form data (display in new tab without saving)
     */' => '/**
     * フォームデータからプレビュー表示する（保存せずに新しいタブで表示）
     */',
    '/**
     * Display trash (soft-deleted pages) list
     */' => '/**
     * ゴミ箱（ソフトデリート済みページ）一覧を表示
     */',
    '/**
     * Empty preview frame for new page creation
     *
     * Used on the new page creation screen where page ID does not exist.
     * Displays an empty page structure with the theme\'s preview layout.
     */' => '/**
     * 新規ページ作成用の空プレビューフレーム
     *
     * ページIDが存在しない新規作成画面で使用する。
     * 空のページ構造をテーマのプレビューレイアウトで表示する。
     */',
    '/**
     * Empty trash (permanently delete all pages)
     */' => '/**
     * ゴミ箱を空にする（全ページを完全削除）
     */',
    '/**
     * Load theme settings for preview frame
     */' => '/**
     * プレビューフレーム用にテーマ設定を読み込む
     */',
    '/**
     * Permanently delete a page in trash (cannot be undone)
     */' => '/**
     * ゴミ箱内のページを完全削除する（取り消し不可）
     */',
    '/**
     * Prepare data required for form display
     *
     * @param  DixlasePagesPage  $page  Page model
     * @param  string|null  $fileContents  Content loaded from file
     * @return array<string, mixed> Form data to pass to view
     */' => '/**
     * フォーム表示に必要なデータを準備する
     *
     * @param  DixlasePagesPage  $page  ページモデル
     * @param  string|null  $fileContents  ファイルから読み込んだコンテンツ
     * @return array<string, mixed> ビューに渡すフォームデータ
     */',
    '/**
     * Preview frame for iframe (display page with theme layout)
     *
     * Loaded in iframe within the admin panel page edit screen.
     * Uses the theme\'s layouts.preview to display with actual theme layout,
     * and updates content in real-time via postMessage.
     */' => '/**
     * iframe用プレビューフレーム（テーマレイアウトでページを表示）
     *
     * 管理画面のページ編集画面内iframeに読み込まれる。
     * テーマの layouts.preview を使用して実際のテーマレイアウトで表示し、
     * postMessage でコンテンツをリアルタイム更新する。
     */',
    '/**
     * Restore a page from trash
     */' => '/**
     * ゴミ箱からページを復元する
     */',
    '/**
     * Save SEO meta information via SEO plugin (optional dependency)
     *
     * @param  array<string, mixed>  $validated  Validated form data
     */' => '/**
     * SEOメタ情報をSEOプラグイン経由で保存する（optional依存）
     *
     * @param  array<string, mixed>  $validated  バリデーション済みフォームデータ
     */',
    '/**
     * Server-side preview rendering (for Blade/GUI editors)
     *
     * Converts content from editor types (Blade, GUI) that cannot be rendered client-side
     * to HTML for real-time preview within iframe.
     */' => '/**
     * サーバーサイドプレビューレンダリング（Blade/GUI エディタ用）
     *
     * iframe内のリアルタイムプレビューで、クライアント側でレンダリングできない
     * エディタタイプ（Blade、GUI）のコンテンツをHTMLに変換して返す。
     */',
    '// Add URL to each page' => '// 各ページにURLを追加',
    '// Allow only valid sort fields' => '// 有効なソートフィールドのみ許可',
    '// Allow only valid sort order' => '// 有効なソート順序のみ許可',
    '// Apply default values from settings (int-backed enums need conversion from slug)' => '// 設定のデフォルト値を適用（int-backed enumにはslugから変換が必要）',
    '// Base URL for displaying page directory URLs' => '// ページディレクトリのURL表示用ベースURL',
    '// Blade editor is disabled in the current version' => '// Bladeエディタは現バージョンでは無効',
    '// Check for slug collision (possibility that a new page with the same slug was created while deleted)' => '// スラッグ衝突チェック（削除中に同じスラッグで新規作成された可能性）',
    '// Convert from slug to enum instance (int-backed enums need conversion from slug)' => '// スラッグからenumインスタンスに変換（int-backed enumはslugから変換が必要）',
    '// Create page (always save content to DB as well = backup)' => '// ページを作成（常にDBにもコンテンツを保存 = バックアップ）',
    '// Current language value (from DB for existing pages, app language for new)' => '// 現在の言語値（既存ページはDBから、新規はアプリ言語）',
    '// Current publish_min_role settings value' => '// 現在の publish_min_role 設定値',
    '// Custom CSS/JS' => '// カスタムCSS/JS',
    '// Data for public permission role slider' => '// 公開権限のロールスライダー用データ',
    '// Display base path for file save' => '// ファイル保存時の表示用ベースパス',
    '// Editor slugs to exclude in Simple mode' => '// Simple mode で除外するエディタースラッグ',
    '// Editor type radio card options (for common components)' => '// エディタータイプのラジオカードオプション（共通コンポーネント用）',
    '// File→DB: Delete file (no need to load since DB always has backup)' => '// ファイル→DB: ファイルを削除（DBには常にバックアップがあるため読み込み不要）',
    '// For file save, also save to file' => '// ファイル保存の場合はファイルにも保存',
    '// For file storage, load content from file' => '// ファイル保存の場合、ファイルからコンテンツを読み込む',
    '// Get content (empty for new page, from file or DB for existing page)' => '// コンテンツ取得（新規ページの場合は空、既存ページはファイルまたはDBから）',
    '// Get page directory settings from database' => '// ページディレクトリ設定をデータベースから取得',
    '// Get settings data' => '// 設定データを取得',
    '// Get sort settings' => '// ソート設定を取得',
    '// Handle storage method changes' => '// 保存方法が変更された場合の処理',
    '// If slug is changed, rename the file' => '// スラッグが変更された場合、ファイルをリネーム',
    '// In preview, always treat as database and directly display the POSTed content' => '// プレビューでは常にdatabaseとして扱い、POSTされたcontentを直接表示する',
    '// Language options (get labels according to locale from translation keys)' => '// 言語オプション（翻訳キーからロケールに応じたラベルを取得）',
    '// Load content column from DB' => '// DBから content カラムを読み込む',
    '// Load content from file' => '// ファイルからコンテンツを読み込む',
    '// Members without public permission can only use draft' => '// 公開権限がないメンバーは下書きのみ',
    '// Only roles with editor permission or higher can be selected (guest/receptionist/contributor excluded)' => '// 編集権限以上のロールのみ選択可能（ゲスト/受付/寄稿者は除外）',
    '// Page directory settings' => '// ページディレクトリ設定',
    '// Pagination (with per-page count support)' => '// ページネーション（件数指定対応）',
    '// Prepare view variables (because @php blocks are prohibited)' => '// ビュー変数を準備（@phpブロック禁止のため）',
    '// Preview URL (separate tab preview)' => '// プレビューURL（別タブプレビュー）',
    '// Public permission check: whether the current member\'s role is equal to or higher than publish_min_role' => '// 公開権限チェック: 現在のメンバーのロールが publish_min_role 以上か',
    '// Radio card options for default status' => '// デフォルトステータスのラジオカードオプション',
    '// Radio card options for editor type (Blade is currently disabled)' => '// エディタータイプのラジオカードオプション（Bladeは現在無効）',
    '// Radio card options for storage type' => '// ストレージタイプのラジオカードオプション',
    '// Record revision (manual because it\'s an explicit save by user)' => '// リビジョン記録（ユーザーの明示保存なので manual）',
    '// Rename CSS/JS files as well' => '// CSS/JSファイルもリネーム',
    '// Return empty if not file storage' => '// ファイル保存でない場合は空を返す',
    '// SEO meta information (only when SEO plugin is enabled and capability is declared)' => '// SEOメタ情報（SEOプラグインが有効かつ capability が宣言されている場合のみ）',
    '// Save CSS/JS files as well' => '// CSS/JSファイルも保存',
    '// Save SEO meta (only when SEO plugin is enabled)' => '// SEOメタ保存（SEOプラグイン有効時のみ）',
    '// Save settings to database' => '// 設定をデータベースに保存',
    '// Search functionality (title, content, slug, description)' => '// 検索機能（タイトル、コンテンツ、スラッグ、説明文）',
    '// Server-side rendering URL (for Blade/GUI editor)' => '// サーバーサイドレンダリングURL（Blade/GUIエディタ用）',
    '// Skipped if there are no changes from the previous revision' => '// 直前リビジョンと差分がない場合はスキップされる',
    '// Soft delete to move to trash (files and SEO meta are deleted on forceDelete)' => '// ソフトデリートでゴミ箱に移動（ファイル・SEOメタ情報は forceDelete 時に削除）',
    '// Status filter' => '// ステータスフィルター',
    '// Status options (for form-select)' => '// ステータスオプション（form-select用）',
    '// Status value including old() (for Alpine.js initialization)' => '// old()込みのステータス値（Alpine.js初期化用）',
    '// Storage save method options (for form-select)' => '// ストレージ保存方法オプション（form-select用）',
    '// Update page (always save content to DB as backup)' => '// ページを更新（常にDBにもコンテンツを保存 = バックアップ）',
    '// editor_type maintains the existing model value (cannot be changed during editing)' => '// editor_type はモデルの既存値を維持（編集時は変更不可）',
    '// iframe preview frame URL (existing page when editing, empty frame when creating new)' => '// iframeプレビューフレームURL（編集時は既存ページ、新規作成時は空フレーム）',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '/**
     * Display preview from form data (display in new tab without saving)
     */' => 'machine',
        '/**
     * Display trash (soft-deleted pages) list
     */' => 'machine',
        '/**
     * Empty preview frame for new page creation
     *
     * Used on the new page creation screen where page ID does not exist.
     * Displays an empty page structure with the theme\'s preview layout.
     */' => 'machine',
        '/**
     * Empty trash (permanently delete all pages)
     */' => 'machine',
        '/**
     * Load theme settings for preview frame
     */' => 'machine',
        '/**
     * Permanently delete a page in trash (cannot be undone)
     */' => 'machine',
        '/**
     * Prepare data required for form display
     *
     * @param  DixlasePagesPage  $page  Page model
     * @param  string|null  $fileContents  Content loaded from file
     * @return array<string, mixed> Form data to pass to view
     */' => 'machine',
        '/**
     * Preview frame for iframe (display page with theme layout)
     *
     * Loaded in iframe within the admin panel page edit screen.
     * Uses the theme\'s layouts.preview to display with actual theme layout,
     * and updates content in real-time via postMessage.
     */' => 'machine',
        '/**
     * Restore a page from trash
     */' => 'machine',
        '/**
     * Save SEO meta information via SEO plugin (optional dependency)
     *
     * @param  array<string, mixed>  $validated  Validated form data
     */' => 'machine',
        '/**
     * Server-side preview rendering (for Blade/GUI editors)
     *
     * Converts content from editor types (Blade, GUI) that cannot be rendered client-side
     * to HTML for real-time preview within iframe.
     */' => 'machine',
        '// Add URL to each page' => 'machine',
        '// Allow only valid sort fields' => 'machine',
        '// Allow only valid sort order' => 'machine',
        '// Apply default values from settings (int-backed enums need conversion from slug)' => 'machine',
        '// Base URL for displaying page directory URLs' => 'machine',
        '// Blade editor is disabled in the current version' => 'machine',
        '// Check for slug collision (possibility that a new page with the same slug was created while deleted)' => 'machine',
        '// Convert from slug to enum instance (int-backed enums need conversion from slug)' => 'machine',
        '// Create page (always save content to DB as well = backup)' => 'machine',
        '// Current language value (from DB for existing pages, app language for new)' => 'machine',
        '// Current publish_min_role settings value' => 'machine',
        '// Custom CSS/JS' => 'machine',
        '// Data for public permission role slider' => 'machine',
        '// Display base path for file save' => 'machine',
        '// Editor slugs to exclude in Simple mode' => 'machine',
        '// Editor type radio card options (for common components)' => 'machine',
        '// File→DB: Delete file (no need to load since DB always has backup)' => 'machine',
        '// For file save, also save to file' => 'machine',
        '// For file storage, load content from file' => 'machine',
        '// Get content (empty for new page, from file or DB for existing page)' => 'machine',
        '// Get page directory settings from database' => 'machine',
        '// Get settings data' => 'machine',
        '// Get sort settings' => 'machine',
        '// Handle storage method changes' => 'machine',
        '// If slug is changed, rename the file' => 'machine',
        '// In preview, always treat as database and directly display the POSTed content' => 'machine',
        '// Language options (get labels according to locale from translation keys)' => 'machine',
        '// Load content column from DB' => 'machine',
        '// Load content from file' => 'machine',
        '// Members without public permission can only use draft' => 'machine',
        '// Only roles with editor permission or higher can be selected (guest/receptionist/contributor excluded)' => 'machine',
        '// Page directory settings' => 'machine',
        '// Pagination (with per-page count support)' => 'machine',
        '// Prepare view variables (because @php blocks are prohibited)' => 'machine',
        '// Preview URL (separate tab preview)' => 'machine',
        '// Public permission check: whether the current member\'s role is equal to or higher than publish_min_role' => 'machine',
        '// Radio card options for default status' => 'machine',
        '// Radio card options for editor type (Blade is currently disabled)' => 'machine',
        '// Radio card options for storage type' => 'machine',
        '// Record revision (manual because it\'s an explicit save by user)' => 'machine',
        '// Rename CSS/JS files as well' => 'machine',
        '// Return empty if not file storage' => 'machine',
        '// SEO meta information (only when SEO plugin is enabled and capability is declared)' => 'machine',
        '// Save CSS/JS files as well' => 'machine',
        '// Save SEO meta (only when SEO plugin is enabled)' => 'machine',
        '// Save settings to database' => 'machine',
        '// Search functionality (title, content, slug, description)' => 'machine',
        '// Server-side rendering URL (for Blade/GUI editor)' => 'machine',
        '// Skipped if there are no changes from the previous revision' => 'machine',
        '// Soft delete to move to trash (files and SEO meta are deleted on forceDelete)' => 'machine',
        '// Status filter' => 'machine',
        '// Status options (for form-select)' => 'machine',
        '// Status value including old() (for Alpine.js initialization)' => 'machine',
        '// Storage save method options (for form-select)' => 'machine',
        '// Update page (always save content to DB as backup)' => 'machine',
        '// editor_type maintains the existing model value (cannot be changed during editing)' => 'machine',
        '// iframe preview frame URL (existing page when editing, empty frame when creating new)' => 'machine',
    ],
];
