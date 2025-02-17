<?php

/**
 * This file is part of MySoftware.
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
            '_insert_after' => 'front', // または '_insert_before' => 'media'
            'text' => 'pages-plugin::admin.nav.pages.text',
            'icon' => 'fas fa-fw fa-calendar-alt',
            'can' => 'manager',
            'children' => [
                'index' => [
                    'text' => 'pages-plugin::admin.nav.pages.index',
                    'route' => 'pages-plugin::admin.pages.index',
                    'icon' => 'fas fa-fw fa-list',
                    'can' => 'manager',
                ],
                'create' => [
                    'text' => 'pages-plugin::admin.nav.pages.create',
                    'route' => 'pages-plugin::admin.pages.create',
                    'icon' => 'fas fa-fw fa-plus',
                    'can' => 'manager',
                ],
            ],
        ],
    ],
];
