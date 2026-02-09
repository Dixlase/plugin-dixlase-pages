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
    // ページ管理
    'pages' => [
        '_insert_after' => 'front',
        'text' => 'dixlase-pages::admin.nav.pages.text',
        'icon' => 'fas fa-fw fa-file-alt',
        'can' => 'admin',
        'children' => [
            'index' => [
                'text' => 'dixlase-pages::admin.nav.pages.index',
                'route' => 'dixlase-pages::admin.pages.index',
                'icon' => 'fas fa-fw fa-file',
                'can' => 'admin',
            ],
            'create' => [
                'text' => 'dixlase-pages::admin.nav.pages.create',
                'route' => 'dixlase-pages::admin.pages.create',
                'icon' => 'fas fa-fw fa-file-circle-plus',
                'can' => 'admin',
            ],
            'settings' => [
                'text' => 'dixlase-pages::admin.nav.pages.settings',
                'route' => 'dixlase-pages::admin.pages.settings',
                'icon' => 'fas fa-fw fa-cog',
                'can' => 'admin',
            ],
        ],
    ],
];
