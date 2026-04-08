<?php

/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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
    'heading' => 'ページ設定',
    'description' => 'ページ管理のディレクトリ、デフォルト値、機能の設定を行います。',
    'basic' => [
        'title' => '基本設定',
        'route_slug' => 'URLスラッグ',
        'route_slug_help' => 'ページのURLパスに使用するスラッグ',
        'default_status' => 'デフォルトステータス',
        'default_editor_type' => 'デフォルトエディタタイプ',
        'default_editor_type_help' => '新規ページ作成時のデフォルトエディタタイプ。',
        'default_storage_type' => 'デフォルト保存方式',
        'default_storage_type_help' => '新規ページ作成時のデフォルト保存方式。',
    ],
    'simple_mode_notice' => '簡単モードでは一部の設定が自動的に構成されます。すべての設定をカスタマイズするには詳細モードに切り替えてください。',
    'publish_permission' => [
        'title' => '公開権限',
        'description' => 'ページを公開できる最低ロールを設定します。このロールに満たないメンバーは下書きとしてのみ保存できます。',
        'min_role' => '公開に必要な最低ロール',
        'min_role_help' => 'このロール以上のメンバーがページの公開・予約公開を行えます。編集権限があってもこのロールに満たないメンバーは下書きのみ作成できます。',
    ],
    'confirm_title' => 'ページ設定の保存',
    'confirm_message' => 'ページ設定を保存してもよろしいですか？',
    'success' => 'ページ設定が更新されました。',
];
