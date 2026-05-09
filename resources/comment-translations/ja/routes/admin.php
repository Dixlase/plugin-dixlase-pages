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
    '/*
|--------------------------------------------------------------------------
| Plugin Admin Panel Routes (Auto-loaded)
|--------------------------------------------------------------------------
|
| This file is automatically loaded by PluginServiceProvider when the plugin
| is enabled. The following middleware are automatically applied:
|
| - admin.ip: IP address filtering
| - auth:member: Admin member authentication
| - verified: Email verification check
| - log.admin.activity: Admin panel operation log
|
| Route prefix: /admin (dynamically retrieved)
| Route name: Fully controlled by plugin (e.g., dixlase-pages::admin.pages.index)
|
*/' => '/*
|--------------------------------------------------------------------------
| プラグイン管理画面ルート（自動読み込み）
|--------------------------------------------------------------------------
|
| このファイルはプラグインが有効化されている場合、PluginServiceProviderによって
| 自動的に読み込まれます。以下のミドルウェアが自動適用されます：
|
| - admin.ip: IPアドレスフィルタリング
| - auth:member: 管理メンバー認証
| - verified: メール認証済みチェック
| - log.admin.activity: 管理画面操作ログ
|
| ルートプレフィックス: /admin（動的に取得）
| ルート名: プラグイン側で完全に制御（例: dixlase-pages::admin.pages.index）
|
*/',
    '// Revisions (view, diff, restore)' => '// リビジョン（閲覧・差分表示・復元）',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '/*
|--------------------------------------------------------------------------
| Plugin Admin Panel Routes (Auto-loaded)
|--------------------------------------------------------------------------
|
| This file is automatically loaded by PluginServiceProvider when the plugin
| is enabled. The following middleware are automatically applied:
|
| - admin.ip: IP address filtering
| - auth:member: Admin member authentication
| - verified: Email verification check
| - log.admin.activity: Admin panel operation log
|
| Route prefix: /admin (dynamically retrieved)
| Route name: Fully controlled by plugin (e.g., dixlase-pages::admin.pages.index)
|
*/' => 'machine',
        '// Revisions (view, diff, restore)' => 'machine',
    ],
];
