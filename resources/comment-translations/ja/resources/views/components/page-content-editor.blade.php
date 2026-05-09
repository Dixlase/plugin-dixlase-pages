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
    '{{--
Layout:
+----------------------------------+---+----------------+
| Main Content                     |[>]| Right Sidebar  |
|  Title                           |   | Slug           |
|  Editor Type (radio cards)       |   | Storage Type   |
|  Content Editor                  |   | Publish        |
|                                  |   |                |
+----------------------------------+---+----------------+
--}}' => '{{--
レイアウト:
+----------------------------------+---+----------------+
| Main Content                     |[>]| Right Sidebar  |
|  Title                           |   | Slug           |
|  Editor Type (radio cards)       |   | Storage Type   |
|  Content Editor                  |   | Publish        |
|                                  |   |                |
+----------------------------------+---+----------------+
--}}',
    '{{-- 1. Editor Type Selection --}}' => '{{-- 1. エディタータイプ選択 --}}',
    '{{-- 3. Content Editor (split pane) --}}' => '{{-- 3. コンテンツエディタ（スプリットペイン） --}}',
    '{{-- 4. Slug --}}' => '{{-- 4. スラッグ --}}',
    '{{-- 4.5 SEO Meta settings (shown only when SEO plugin is enabled and seo-meta capability is declared) --}}' => '{{-- 4.5 SEOメタ設定（SEOプラグイン有効 かつ seo-meta capability 宣言時のみ表示） --}}',
    '{{-- 5. Save Method (creation only: when editing, integrated into the meta info section below preview) --}}' => '{{-- 5. 保存方法（作成時のみ：編集時はプレビュー直下のメタ情報セクションに統合済み） --}}',
    '{{-- 6.5. Language Selection (creation only: when editing, integrated into meta info) --}}' => '{{-- 6.5. 言語選択（作成時のみ：編集時はメタ情報に統合） --}}',
    '{{-- 7. Public settings --}}' => '{{-- 7. 公開設定 --}}',
    '{{-- <x-dynamic-component> resolves components at runtime, so it\'s safe even when the SEO plugin is disabled --}}' => '{{-- <x-dynamic-component> は実行時にコンポーネントを解決するため、SEOプラグインが無効でも安全 --}}',
    '{{-- ===== Main Content Area ===== --}}' => '{{-- ===== メインコンテンツエリア ===== --}}',
    '{{-- ===== Right Sidebar ===== --}}' => '{{-- ===== 右サイドバー ===== --}}',
    '{{-- CSS tab (HTML editor only) --}}' => '{{-- CSS タブ（HTMLエディタ時のみ） --}}',
    '{{-- Create mode: Editor type radio card selection --}}' => '{{-- 作成時: エディタータイプ ラジオカード選択 --}}',
    '{{-- Display info for file storage --}}' => '{{-- ファイル保存時の情報表示 --}}',
    '{{-- Edit mode: Show preview toggle only (editor type, language, storage format are shown in right column meta info) --}}' => '{{-- 編集時: プレビュートグルのみ表示（エディタータイプ・言語・保存形式は右カラムのメタ情報に表示） --}}',
    '{{-- Editor pane --}}' => '{{-- エディタペイン --}}',
    '{{-- GUI editor --}}' => '{{-- GUI エディタ --}}',
    '{{-- In edit mode, send meta info via hidden input --}}' => '{{-- 編集時はメタ情報を hidden input で送信 --}}',
    '{{-- JS tab (HTML editor only) --}}' => '{{-- JS タブ（HTMLエディタ時のみ） --}}',
    '{{-- Meta info (edit mode: placed directly below preview, above title) --}}' => '{{-- メタ情報（編集時：プレビューの直下、タイトルの上に配置） --}}',
    '{{-- On creation: save format is selectable --}}' => '{{-- 作成時: 保存形式を選択可能 --}}',
    '{{-- Preview in new tab --}}' => '{{-- 別タブプレビュー --}}',
    '{{-- Preview pane (edit mode only) --}}' => '{{-- プレビューペイン（編集時のみ） --}}',
    '{{-- Preview toggle button (create mode) --}}' => '{{-- プレビュートグルボタン（作成時） --}}',
    '{{-- Publication date/time (shown only for scheduled public) --}}' => '{{-- 公開日時（予約公開の場合のみ表示） --}}',
    '{{-- Revision History (edit only) --}}' => '{{-- リビジョン履歴（編集時のみ） --}}',
    '{{-- Scroll button (edit mode only) --}}' => '{{-- スクロールボタン（編集時のみ） --}}',
    '{{-- Simple mode: save format is fixed via hidden input --}}' => '{{-- Simple mode: 保存形式を hidden input で固定 --}}',
    '{{-- Status --}}' => '{{-- ステータス --}}',
    '{{-- Tab navigation (HTML editor only) --}}' => '{{-- タブナビゲーション（HTMLエディタ時のみ） --}}',
    '{{-- Text editor (common for HTML / Markdown) --}}' => '{{-- テキストエディタ（HTML / Markdown 共通） --}}',
    '{{-- Title --}}' => '{{-- タイトル --}}',
    '{{-- URL Preview --}}' => '{{-- URLプレビュー --}}',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '{{--
Layout:
+----------------------------------+---+----------------+
| Main Content                     |[>]| Right Sidebar  |
|  Title                           |   | Slug           |
|  Editor Type (radio cards)       |   | Storage Type   |
|  Content Editor                  |   | Publish        |
|                                  |   |                |
+----------------------------------+---+----------------+
--}}' => 'machine',
        '{{-- 1. Editor Type Selection --}}' => 'machine',
        '{{-- 3. Content Editor (split pane) --}}' => 'machine',
        '{{-- 4. Slug --}}' => 'machine',
        '{{-- 4.5 SEO Meta settings (shown only when SEO plugin is enabled and seo-meta capability is declared) --}}' => 'machine',
        '{{-- 5. Save Method (creation only: when editing, integrated into the meta info section below preview) --}}' => 'machine',
        '{{-- 6.5. Language Selection (creation only: when editing, integrated into meta info) --}}' => 'machine',
        '{{-- 7. Public settings --}}' => 'machine',
        '{{-- <x-dynamic-component> resolves components at runtime, so it\'s safe even when the SEO plugin is disabled --}}' => 'machine',
        '{{-- ===== Main Content Area ===== --}}' => 'machine',
        '{{-- ===== Right Sidebar ===== --}}' => 'machine',
        '{{-- CSS tab (HTML editor only) --}}' => 'machine',
        '{{-- Create mode: Editor type radio card selection --}}' => 'machine',
        '{{-- Display info for file storage --}}' => 'machine',
        '{{-- Edit mode: Show preview toggle only (editor type, language, storage format are shown in right column meta info) --}}' => 'machine',
        '{{-- Editor pane --}}' => 'machine',
        '{{-- GUI editor --}}' => 'machine',
        '{{-- In edit mode, send meta info via hidden input --}}' => 'machine',
        '{{-- JS tab (HTML editor only) --}}' => 'machine',
        '{{-- Meta info (edit mode: placed directly below preview, above title) --}}' => 'machine',
        '{{-- On creation: save format is selectable --}}' => 'machine',
        '{{-- Preview in new tab --}}' => 'machine',
        '{{-- Preview pane (edit mode only) --}}' => 'machine',
        '{{-- Preview toggle button (create mode) --}}' => 'machine',
        '{{-- Publication date/time (shown only for scheduled public) --}}' => 'machine',
        '{{-- Revision History (edit only) --}}' => 'machine',
        '{{-- Scroll button (edit mode only) --}}' => 'machine',
        '{{-- Simple mode: save format is fixed via hidden input --}}' => 'machine',
        '{{-- Status --}}' => 'machine',
        '{{-- Tab navigation (HTML editor only) --}}' => 'machine',
        '{{-- Text editor (common for HTML / Markdown) --}}' => 'machine',
        '{{-- Title --}}' => 'machine',
        '{{-- URL Preview --}}' => 'machine',
    ],
];
