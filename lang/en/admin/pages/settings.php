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

return [
    'heading' => 'Page Settings',
    'description' => 'Configure page management settings including directory, defaults, and features.',
    'basic' => [
        'title' => 'Basic Settings',
        'route_slug' => 'URL Slug',
        'route_slug_help' => 'URL slug used as the directory path for pages',
        'default_status' => 'Default Status',
        'default_editor_type' => 'Default Editor Type',
        'default_editor_type_help' => 'Default editor type when creating new pages.',
        'default_storage_type' => 'Default Storage Method',
        'default_storage_type_help' => 'Default content storage method when creating new pages.',
    ],
    'simple_mode_notice' => 'Some settings are automatically configured in Simple mode. Switch to Advanced mode to customize all settings.',
    'publish_permission' => [
        'title' => 'Publish Permission',
        'description' => 'Set the minimum role required to publish pages. Members below this role can only save pages as drafts.',
        'min_role' => 'Minimum Role for Publishing',
        'min_role_help' => 'Members with this role or higher can publish and schedule pages. Members with editing access but below this role can only create drafts.',
    ],
    'confirm_title' => 'Save Page Settings',
    'confirm_message' => 'Are you sure you want to save the page settings?',
    'success' => 'Page settings have been updated successfully.',
];
