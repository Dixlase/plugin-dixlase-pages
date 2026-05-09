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
    '// (published or scheduled with publication date in the past)' => '// (published または scheduled で公開日時が過去のもの)',
    '// Custom CSS/JS asset routes (defined before page display routes)' => '// カスタム CSS/JS アセットルート（ページ表示ルートの前に定義）',
    '// Get settings from database, or use default values if none exist' => '// データベースから設定を取得、なければデフォルト値を使用',
    '// Individual pages' => '// 個別ページ',
    '// Only public pages if not logged in' => '// ログインしていない場合は公開済みページのみ',
    '// Page display routes' => '// ページ表示ルート',
    '// Prepare front view variables' => '// フロントビュー変数を準備',
    '// Show all pages if logged in to admin panel (preview feature)' => '// 管理画面にログインしている場合は全てのページを表示（プレビュー機能）',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '// (published or scheduled with publication date in the past)' => 'machine',
        '// Custom CSS/JS asset routes (defined before page display routes)' => 'machine',
        '// Get settings from database, or use default values if none exist' => 'machine',
        '// Individual pages' => 'machine',
        '// Only public pages if not logged in' => 'machine',
        '// Page display routes' => 'machine',
        '// Prepare front view variables' => 'machine',
        '// Show all pages if logged in to admin panel (preview feature)' => 'machine',
    ],
];
