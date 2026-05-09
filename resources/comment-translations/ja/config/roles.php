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
 * Plugin default permission settings
 *
 * Defines default permissions for each menu/feature.
 * Only when changed in the admin panel, differences are saved to the role_permission_overrides table.
 *
 * Structure follows the same nested format as the nav structure in config/admin.php.
 */' => '/**
 * プラグインのデフォルト権限設定
 *
 * 各メニュー/機能に対するデフォルトの権限を定義します。
 * 管理画面で変更された場合のみ、role_permission_overrides テーブルに差分が保存されます。
 *
 * 構造は config/admin.php の nav 構造と同じネスト形式です。
 */',
    '// Page management' => '// ページ管理',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '/**
 * Plugin default permission settings
 *
 * Defines default permissions for each menu/feature.
 * Only when changed in the admin panel, differences are saved to the role_permission_overrides table.
 *
 * Structure follows the same nested format as the nav structure in config/admin.php.
 */' => 'machine',
        '// Page management' => 'machine',
    ],
];
