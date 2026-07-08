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
    // Page Management
    'pages' => [
        '_insert_after' => 'front',
        'text' => 'dixlase-pages::admin/navigation.pages.text',
        'icon' => 'fas fa-fw fa-file-alt',
        // Route the sidebar permission check through this plugin's
        // config/admin/roles.php. Without it the check falls back to core's
        // PermissionRegistry (no `pages` entry) and the menu is hidden for
        // everyone below SUPER_ADMIN. Value is the plugin directory basename.
        'plugin_slug' => 'DixlasePages',
        'can' => 'admin',
        'children' => [
            'index' => [
                'text' => 'dixlase-pages::admin/navigation.pages.index',
                'route' => 'dixlase-pages::admin.pages.index',
                'icon' => 'fas fa-fw fa-file',
                'can' => 'admin',
            ],
            'create' => [
                'text' => 'dixlase-pages::admin/navigation.pages.create',
                'route' => 'dixlase-pages::admin.pages.create',
                'icon' => 'fas fa-fw fa-file-circle-plus',
                'can' => 'admin',
            ],
            'settings' => [
                'text' => 'dixlase-pages::admin/navigation.pages.settings',
                'route' => 'dixlase-pages::admin.pages.settings',
                'icon' => 'fas fa-fw fa-cog',
                'can' => 'admin',
            ],
        ],
    ],
];
