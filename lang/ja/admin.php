<?php

/**
 * This file is part of DixlasePages.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

return [
    'nav' => [
        'pages' => [
            'text' => 'ページ管理',
            'index' => 'ページマスター',
            'create' => 'ページ作成',
        ],
    ],

    'pages' => [
        'search_section' => '検索・フィルター',
        'search_placeholder' => 'ページタイトルで検索',
        'all_status' => 'すべてのステータス',
        'status_filter' => 'ステータスフィルター',
        'list_section' => 'ページ一覧',
        'table_label' => 'ページ一覧テーブル',
        'index' => [
            'heading' => 'ページマスター',
        ],
        'create' => [
            'heading' => '新規ページ作成',
        ],
        'edit' => [
            'heading' => 'ページ編集',
        ],
    ],

    'actions' => [
        'delete_confirm' => 'このページを削除してもよろしいですか？',
    ],

    'messages' => [
        'no_pages_found' => 'ページが見つかりません。',
        'no_pages_description' => '新しいページを作成してください。',
        'total_pages' => '全:total件',
        'per_page' => '表示件数',
        'create_confirmation_message' => 'この内容でページを作成しますか？',
        'update_confirmation_message' => 'この内容でページを更新しますか？',
    ],

    'validation' => [
        'title_required' => 'タイトルは必須です。',
        'title_max' => 'タイトルは255文字以内で入力してください。',
        'slug_format' => 'スラッグは半角英数字とハイフンのみ使用できます。',
        'slug_unique' => 'このスラッグは既に使用されています。',
        'content_required' => 'コンテンツは必須です。',
        'status_required' => 'ステータスは必須です。',
        'published_at_date' => '公開日時は正しい日付形式で入力してください。',
        'published_at_future' => '公開日時は現在時刻以降を指定してください。',
    ],
];
