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
    'heading' => 'Page Settings',
    'description' => 'Configure page management settings including directory, defaults, and features.',
    'basic' => [
        'title' => 'Basic Settings',
        'pages_directory' => 'Pages Directory',
        'pages_directory_help' => 'Directory name used in page URLs',
        'default_status' => 'Default Status',
        'default_editor_type' => 'Default Editor Type',
        'default_editor_type_help' => 'Default editor type when creating new pages.',
        'default_storage_type' => 'Default Storage Method',
        'default_storage_type_help' => 'Default content storage method when creating new pages.',
    ],
    'features' => [
        'title' => 'Feature Settings',
        'blade_enabled' => 'Allow Blade Editor',
        'blade_enabled_help' => 'Enable the Blade editor for new pages. Only enable if trusted administrators will use it.',
        'scheduled_publish_enabled' => 'Enable Scheduled Publishing',
        'scheduled_publish_enabled_help' => 'Allow pages to be scheduled for future publication.',
    ],
    'confirm_title' => 'Save Page Settings',
    'confirm_message' => 'Are you sure you want to save the page settings?',
    'success' => 'Page settings have been updated successfully.',
];
