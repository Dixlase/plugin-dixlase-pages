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
     * Constructor
     * Retrieves plugin slug from plugin.json and sets the base path
     */' => '/**
     * コンストラクタ
     * plugin.jsonからプラグインスラッグを取得してベースパスを設定
     */',
    '/**
     * Delete CSS file
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @return bool True on successful deletion
     */' => '/**
     * CSS ファイルを削除する
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @return bool 削除成功時はtrue
     */',
    '/**
     * Delete JS file
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @return bool True on successful deletion
     */' => '/**
     * JS ファイルを削除する
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @return bool 削除成功時はtrue
     */',
    '/**
     * Delete all files related to a slug (flat structure support)
     * Search and delete files matching the slug, not directories
     *
     * @param  string  $slug  Slug
     * @return bool True if all deletions succeed
     */' => '/**
     * スラッグに関連するすべてのファイルを削除する（フラット構造対応）
     * ディレクトリではなく、スラッグにマッチするファイルを検索して削除
     *
     * @param  string  $slug  スラッグ
     * @return bool すべて削除成功時はtrue
     */',
    '/**
     * Rename CSS/JS files when slug changes
     *
     * @param  string  $oldSlug  Old slug
     * @param  string  $newSlug  New slug
     * @param  string  $locale  Language code
     * @return bool True on successful rename
     */' => '/**
     * スラッグ変更時にCSS/JSファイルをリネームする
     *
     * @param  string  $oldSlug  旧スラッグ
     * @param  string  $newSlug  新スラッグ
     * @param  string  $locale  言語コード
     * @return bool リネーム成功時はtrue
     */',
    '/**
     * Rename file when slug changes (single locale support)
     *
     * @param  string  $oldSlug  Old slug
     * @param  string  $newSlug  New slug
     * @param  string  $editorType  Editor type
     * @param  string  $locale  Language code
     * @return bool True on successful rename
     */' => '/**
     * スラッグ変更時にファイルをリネームする（単一ロケール対応）
     *
     * @param  string  $oldSlug  旧スラッグ
     * @param  string  $newSlug  新スラッグ
     * @param  string  $editorType  エディタータイプ
     * @param  string  $locale  言語コード
     * @return bool リネーム成功時はtrue
     */',
    '/**
     * Retrieve CSS content (DB or file)
     *
     * @param  DixlasePagesPage  $page  Page model
     * @param  string|null  $locale  Language code (current language if null)
     * @return string|null Content
     */' => '/**
     * CSS コンテンツを取得する（DB or ファイル）
     *
     * @param  DixlasePagesPage  $page  ページモデル
     * @param  string|null  $locale  言語コード（nullの場合は現在の言語）
     * @return string|null コンテンツ
     */',
    '/**
     * Retrieve CSS file path
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @return string File path
     */' => '/**
     * CSS ファイルパスを取得する
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @return string ファイルパス
     */',
    '/**
     * Retrieve JS content (DB or file)
     *
     * @param  DixlasePagesPage  $page  Page model
     * @param  string|null  $locale  Language code (current language if null)
     * @return string|null Content
     */' => '/**
     * JS コンテンツを取得する（DB or ファイル）
     *
     * @param  DixlasePagesPage  $page  ページモデル
     * @param  string|null  $locale  言語コード（nullの場合は現在の言語）
     * @return string|null コンテンツ
     */',
    '/**
     * Retrieve JS file path
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @return string File path
     */' => '/**
     * JS ファイルパスを取得する
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @return string ファイルパス
     */',
    '/**
     * Retrieve file path (flat structure)
     * Structure: {basePath}/{slug}.{extension} or {basePath}/{slug}.{locale}.{extension}
     * Default language does not append language code to filename
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @param  string  $editorType  Editor type
     * @return string File path
     */' => '/**
     * ファイルパスを取得する（フラット構造）
     * 構造: {basePath}/{slug}.{extension} または {basePath}/{slug}.{locale}.{extension}
     * デフォルト言語はファイル名に言語コードを付けない
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @param  string  $editorType  エディタータイプ
     * @return string ファイルパス
     */',
    '/**
     * Save CSS content to file
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @param  string  $content  Content
     * @return bool True on successful save
     */' => '/**
     * CSS コンテンツをファイルに保存する
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @param  string  $content  コンテンツ
     * @return bool 保存成功時はtrue
     */',
    '/**
     * Save JS content to file
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @param  string  $content  Content
     * @return bool True on successful save
     */' => '/**
     * JS コンテンツをファイルに保存する
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @param  string  $content  コンテンツ
     * @return bool 保存成功時はtrue
     */',
    '/**
 * Page content file management service
 * Manages file-based content storage
 * Uses ManagesContentFiles trait to provide common functionality (no inheritance to avoid license propagation)
 *
 * File structure: storage/app/private/{plugin-slug}/{page-slug}.{extension}
 * Plugin slug is dynamically retrieved from plugin.json
 */' => '/**
 * ページコンテンツファイル管理サービス
 * ファイルベースのコンテンツ保存を管理
 * ManagesContentFilesトレイトを使用して共通機能を提供（ライセンス伝搬を避けるため継承なし）
 *
 * ファイル構造: storage/app/private/{plugin-slug}/{page-slug}.{extension}
 * プラグインスラッグはplugin.jsonから動的に取得
 */',
    '// Default language does not append language code to filename' => '// デフォルト言語はファイル名に言語コードを付けない',
    '// Delete files matching {slug}.{ext} or {slug}.{locale}.{ext} pattern' => '// {slug}.{ext} または {slug}.{locale}.{ext} パターンに一致するファイルを削除',
    '// Rename CSS file' => '// CSS ファイルのリネーム',
    '// Rename JS file' => '// JS ファイルのリネーム',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '/**
     * Constructor
     * Retrieves plugin slug from plugin.json and sets the base path
     */' => 'machine',
        '/**
     * Delete CSS file
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @return bool True on successful deletion
     */' => 'machine',
        '/**
     * Delete JS file
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @return bool True on successful deletion
     */' => 'machine',
        '/**
     * Delete all files related to a slug (flat structure support)
     * Search and delete files matching the slug, not directories
     *
     * @param  string  $slug  Slug
     * @return bool True if all deletions succeed
     */' => 'machine',
        '/**
     * Rename CSS/JS files when slug changes
     *
     * @param  string  $oldSlug  Old slug
     * @param  string  $newSlug  New slug
     * @param  string  $locale  Language code
     * @return bool True on successful rename
     */' => 'machine',
        '/**
     * Rename file when slug changes (single locale support)
     *
     * @param  string  $oldSlug  Old slug
     * @param  string  $newSlug  New slug
     * @param  string  $editorType  Editor type
     * @param  string  $locale  Language code
     * @return bool True on successful rename
     */' => 'machine',
        '/**
     * Retrieve CSS content (DB or file)
     *
     * @param  DixlasePagesPage  $page  Page model
     * @param  string|null  $locale  Language code (current language if null)
     * @return string|null Content
     */' => 'machine',
        '/**
     * Retrieve CSS file path
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @return string File path
     */' => 'machine',
        '/**
     * Retrieve JS content (DB or file)
     *
     * @param  DixlasePagesPage  $page  Page model
     * @param  string|null  $locale  Language code (current language if null)
     * @return string|null Content
     */' => 'machine',
        '/**
     * Retrieve JS file path
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @return string File path
     */' => 'machine',
        '/**
     * Retrieve file path (flat structure)
     * Structure: {basePath}/{slug}.{extension} or {basePath}/{slug}.{locale}.{extension}
     * Default language does not append language code to filename
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @param  string  $editorType  Editor type
     * @return string File path
     */' => 'machine',
        '/**
     * Save CSS content to file
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @param  string  $content  Content
     * @return bool True on successful save
     */' => 'machine',
        '/**
     * Save JS content to file
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @param  string  $content  Content
     * @return bool True on successful save
     */' => 'machine',
        '/**
 * Page content file management service
 * Manages file-based content storage
 * Uses ManagesContentFiles trait to provide common functionality (no inheritance to avoid license propagation)
 *
 * File structure: storage/app/private/{plugin-slug}/{page-slug}.{extension}
 * Plugin slug is dynamically retrieved from plugin.json
 */' => 'machine',
        '// Default language does not append language code to filename' => 'machine',
        '// Delete files matching {slug}.{ext} or {slug}.{locale}.{ext} pattern' => 'machine',
        '// Rename CSS file' => 'machine',
        '// Rename JS file' => 'machine',
    ],
];
