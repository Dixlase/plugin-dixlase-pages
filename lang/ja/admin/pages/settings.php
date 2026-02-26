<?php

/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
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
        'pages_directory' => 'ページディレクトリ',
        'pages_directory_help' => 'ページのURLに使用するディレクトリ名',
        'default_status' => 'デフォルトステータス',
        'status_published' => '公開',
        'status_draft' => '下書き',
        'default_editor_type' => 'デフォルトエディタタイプ',
        'default_editor_type_help' => '新規ページ作成時のデフォルトエディタタイプ。',
        'default_storage_type' => 'デフォルト保存方式',
        'default_storage_type_help' => '新規ページ作成時のデフォルト保存方式。',
    ],
    'features' => [
        'title' => '機能設定',
        'blade_enabled' => 'Bladeエディタを許可',
        'blade_enabled_help' => 'Bladeエディタを有効にします。信頼できる管理者のみが使用する場合に有効にしてください。',
        'scheduled_publish_enabled' => 'スケジュール公開を有効にする',
        'scheduled_publish_enabled_help' => 'ページの予約公開を許可します。',
    ],
    'confirm_title' => 'ページ設定の保存',
    'confirm_message' => 'ページ設定を保存してもよろしいですか？',
    'success' => 'ページ設定が更新されました。',
];
