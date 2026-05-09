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
| Plugin API Routes
|--------------------------------------------------------------------------
|
| API endpoints used in the admin panel
| Middleware: admin.ip, auth:member
|
*/' => '/*
|--------------------------------------------------------------------------
| プラグインAPIルート
|--------------------------------------------------------------------------
|
| 管理画面で使用するAPIエンドポイント
| ミドルウェア: admin.ip, auth:member
|
*/',
    '// Get content by save method and editor type' => '// 保存方法・エディタータイプ別のコンテンツ取得',
    '// Get file content by editor type' => '// エディタータイプ別のファイルコンテンツ取得',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '/*
|--------------------------------------------------------------------------
| Plugin API Routes
|--------------------------------------------------------------------------
|
| API endpoints used in the admin panel
| Middleware: admin.ip, auth:member
|
*/' => 'machine',
        '// Get content by save method and editor type' => 'machine',
        '// Get file content by editor type' => 'machine',
    ],
];
