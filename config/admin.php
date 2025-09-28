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
    // プラグイン設定画面のルート名
    'settings_route' => 'admin.dixlase-pages::admin.pages.settings',
    
    'nav' => [
        'pages' => [
            '_insert_after' => 'front', // フロントページ管理のあとに追加、または '_insert_before' => 'media'
            'text' => 'dixlase-pages::admin.nav.pages.text',
            'icon' => 'fas fa-fw fa-file-alt', // ページ管理
            'can' => 'admin',
            'children' => [
                'index' => [
                    'text' => 'dixlase-pages::admin.nav.pages.index',
                    'route' => 'admin.dixlase-pages::admin.pages.index',
                    'icon' => 'fas fa-fw fa-file', // ページ一覧
                    'can' => 'admin',
                ],
                'create' => [
                    'text' => 'dixlase-pages::admin.nav.pages.create',
                    'route' => 'admin.dixlase-pages::admin.pages.create',
                    'icon' => 'fas fa-fw fa-file-circle-plus', // ページ作成
                    'can' => 'admin',
                ],
                'settings' => [
                    'text' => 'dixlase-pages::admin.nav.pages.settings',
                    'route' => 'admin.dixlase-pages::admin.pages.settings',
                    'icon' => 'fas fa-fw fa-cog', // 設定
                    'can' => 'admin',
                ],
            ],
        ],
    ],
];
