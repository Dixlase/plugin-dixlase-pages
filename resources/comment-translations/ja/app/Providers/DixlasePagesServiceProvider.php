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
     * Get theme plugin view override path
     */' => '/**
     * テーマのプラグインビュー上書きパスを取得
     */',
    '/**
     * Register plugin views (supports custom / theme overrides)
     *
     * Search priority:
     * 1. /custom/plugins/DixlasePages/resources/views/ — Site-specific customization
     * 2. themes/{active theme}/plugins/DixlasePages/resources/views/ — Theme overrides
     * 3. plugins/DixlasePages/resources/views/ — Plugin defaults
     */' => '/**
     * プラグインビューを登録（custom / テーマによる上書きをサポート）
     *
     * 検索優先順位:
     * 1. /custom/plugins/DixlasePages/resources/views/ — サイト固有カスタマイズ
     * 2. themes/{有効テーマ}/plugins/DixlasePages/resources/views/ — テーマによる上書き
     * 3. plugins/DixlasePages/resources/views/ — プラグインのデフォルト
     */',
    '/**
     * Register route slug provider
     */' => '/**
     * ルートスラッグプロバイダーを登録
     */',
    '/**
     * Return route slugs managed by the plugin
     *
     * @return array<RegisteredSlug>
     */' => '/**
     * プラグインが管理するルートスラッグを返す
     *
     * @return array<RegisteredSlug>
     */',
    '// 1. Overrides from /custom/ (highest priority)' => '// 1. /custom/ からの上書き（最優先）',
    '// 2. Overrides from active theme' => '// 2. 有効テーマからの上書き',
    '// 3. Plugin default views' => '// 3. プラグインのデフォルトビュー',
    '// Ignore if theme is not set' => '// テーマ未設定時は無視',
    '// Note: Routes (routes/web.php, routes/admin.php, routes/api.php) are auto-loaded by PluginServiceProvider' => '// 注: ルート（routes/web.php, routes/admin.php, routes/api.php）はPluginServiceProviderが自動読み込み',
    '// Register LinkableProvider' => '// LinkableProviderを登録',
    '// Register migrations' => '// マイグレーションの登録',
    '// Register route slug provider' => '// ルートスラッグプロバイダーの登録',
    '// Register translation files' => '// 翻訳ファイルの登録',
    '// Register views (prioritize theme overrides)' => '// ビューの登録（テーマによる上書きを優先）',
    '// Tag it so it can be retrieved from the menu plugin' => '// タグ付けして、メニュープラグインから取得できるようにする',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '/**
     * Get theme plugin view override path
     */' => 'machine',
        '/**
     * Register plugin views (supports custom / theme overrides)
     *
     * Search priority:
     * 1. /custom/plugins/DixlasePages/resources/views/ — Site-specific customization
     * 2. themes/{active theme}/plugins/DixlasePages/resources/views/ — Theme overrides
     * 3. plugins/DixlasePages/resources/views/ — Plugin defaults
     */' => 'machine',
        '/**
     * Register route slug provider
     */' => 'machine',
        '/**
     * Return route slugs managed by the plugin
     *
     * @return array<RegisteredSlug>
     */' => 'machine',
        '// 1. Overrides from /custom/ (highest priority)' => 'machine',
        '// 2. Overrides from active theme' => 'machine',
        '// 3. Plugin default views' => 'machine',
        '// Ignore if theme is not set' => 'machine',
        '// Note: Routes (routes/web.php, routes/admin.php, routes/api.php) are auto-loaded by PluginServiceProvider' => 'machine',
        '// Register LinkableProvider' => 'machine',
        '// Register migrations' => 'machine',
        '// Register route slug provider' => 'machine',
        '// Register translation files' => 'machine',
        '// Register views (prioritize theme overrides)' => 'machine',
        '// Tag it so it can be retrieved from the menu plugin' => 'machine',
    ],
];
