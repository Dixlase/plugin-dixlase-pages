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

use App\Enums\MemberRole;

/**
 * Plugin default permission settings
 *
 * Defines default permissions for each menu/feature.
 * Only when changed in the admin panel, differences are saved to the role_permission_overrides table.
 *
 * Structure follows the same nested format as the nav structure in config/admin.php.
 */

return [
    'permissions' => [
        // Page management
        'pages' => [
            'children' => [
                'index' => [
                    'access_roles' => MemberRole::EDITOR->value,
                    'view_roles' => MemberRole::EDITOR->value,
                ],
                'create' => [
                    'access_roles' => MemberRole::EDITOR->value,
                    'view_roles' => MemberRole::EDITOR->value,
                ],
                // Page settings include publish_min_role (who may publish) and
                // route_slug (public URL structure). Admins may VIEW the
                // settings, but only SUPER_ADMIN may EDIT them, so a delegated
                // admin cannot widen publish rights or change the site's page
                // URL scheme.
                'settings' => [
                    'access_roles' => MemberRole::SUPER_ADMIN->value,
                    'view_roles' => MemberRole::ADMIN->value,
                ],
            ],
        ],
    ],
];
