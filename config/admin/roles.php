<?php

/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

/*
 * Every admin route needs an entry here, not just the ones that appear in the
 * sidebar.
 *
 * Core's EnsurePluginAdminAccess derives the permission key from the route
 * name and falls back to ADMIN when the plugin declares nothing. Only index
 * and create were declared, so an EDITOR could open the page list and the
 * create form and then get a 403 on save -- the screens were reachable and
 * useless. The keys below cover the endpoints those screens submit to.
 *
 * The split is by whether an action can be taken back:
 *
 *   EDITOR  authoring, and deletions that land in the trash
 *   ADMIN   permanent deletion (trash.empty, trash.force-destroy) and
 *           revision protection, which decides what may be deleted later
 *
 * settings stays SUPER_ADMIN-only to edit: it carries publish_min_role and
 * route_slug, so a delegated admin must not be able to widen publish rights
 * or change the site's URL scheme.
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
                // Authoring: the endpoints the list and create screens submit
                // to. Without these the two entries above lead nowhere.
                'store' => [
                    'access_roles' => MemberRole::EDITOR->value,
                    'view_roles' => MemberRole::EDITOR->value,
                ],
                'show' => [
                    'access_roles' => MemberRole::EDITOR->value,
                    'view_roles' => MemberRole::EDITOR->value,
                ],
                'edit' => [
                    'access_roles' => MemberRole::EDITOR->value,
                    'view_roles' => MemberRole::EDITOR->value,
                ],
                'update' => [
                    'access_roles' => MemberRole::EDITOR->value,
                    'view_roles' => MemberRole::EDITOR->value,
                ],
                // Preview renders the draft the author is working on. It no
                // longer executes Blade (see pages#19), so it is ordinary
                // authoring.
                'preview' => [
                    'access_roles' => MemberRole::EDITOR->value,
                    'view_roles' => MemberRole::EDITOR->value,
                ],
                'preview-render' => [
                    'access_roles' => MemberRole::EDITOR->value,
                    'view_roles' => MemberRole::EDITOR->value,
                ],
                'preview-frame' => [
                    'access_roles' => MemberRole::EDITOR->value,
                    'view_roles' => MemberRole::EDITOR->value,
                ],
                'preview-frame-new' => [
                    'access_roles' => MemberRole::EDITOR->value,
                    'view_roles' => MemberRole::EDITOR->value,
                ],
                // Soft delete: the page goes to the trash and can be restored.
                'destroy' => [
                    'access_roles' => MemberRole::EDITOR->value,
                    'view_roles' => MemberRole::EDITOR->value,
                ],
                // Nested, not written as 'trash.empty' etc. PermissionRegistry
                // walks the array level by level, so a dotted key at this level
                // never matches and the route silently inherits whatever the
                // parent says -- which would have handed permanent deletion to
                // EDITOR here.
                'trash' => [
                    'access_roles' => MemberRole::EDITOR->value,
                    'view_roles' => MemberRole::EDITOR->value,
                    'children' => [
                        'restore' => [
                            'access_roles' => MemberRole::EDITOR->value,
                            'view_roles' => MemberRole::EDITOR->value,
                        ],
                        // Permanent deletion. Nothing brings these back, so
                        // they stay with ADMIN even though the trash screen
                        // itself is EDITOR.
                        'empty' => [
                            'access_roles' => MemberRole::ADMIN->value,
                            'view_roles' => MemberRole::ADMIN->value,
                        ],
                        'force-destroy' => [
                            'access_roles' => MemberRole::ADMIN->value,
                            'view_roles' => MemberRole::ADMIN->value,
                        ],
                    ],
                ],
                // Revisions: reading, restoring and annotating are authoring.
                // No access_roles here because there is no bare `revisions`
                // route -- every revision endpoint is one of the children.
                'revisions' => [
                    'children' => [
                        'index' => [
                            'access_roles' => MemberRole::EDITOR->value,
                            'view_roles' => MemberRole::EDITOR->value,
                        ],
                        'show' => [
                            'access_roles' => MemberRole::EDITOR->value,
                            'view_roles' => MemberRole::EDITOR->value,
                        ],
                        'restore' => [
                            'access_roles' => MemberRole::EDITOR->value,
                            'view_roles' => MemberRole::EDITOR->value,
                        ],
                        'note' => [
                            'access_roles' => MemberRole::EDITOR->value,
                            'view_roles' => MemberRole::EDITOR->value,
                        ],
                        // Protection decides which revisions may be pruned
                        // later, so it sits with the permanent-deletion group
                        // rather than with authoring.
                        'protect' => [
                            'access_roles' => MemberRole::ADMIN->value,
                            'view_roles' => MemberRole::ADMIN->value,
                        ],
                    ],
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
