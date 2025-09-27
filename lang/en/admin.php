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
            'text' => 'Page Management',
            'index' => 'Page Master',
            'create' => 'Create Page',
        ],
    ],

    'pages' => [
        'index' => [
            'heading' => 'Page Master',
        ],
        'create' => [
            'heading' => 'Create Page',
        ],
        'edit' => [
            'heading' => 'Edit Page',
        ],
        'search_section' => 'Search & Filter',
        'search_placeholder' => 'Search by page title',
        'all_status' => 'All Status',
        'status_filter' => 'Status Filter',
        'list_section' => 'Page List',
        'table_label' => 'Page List Table',
    ],

    'actions' => [
        'delete_confirm' => 'Are you sure you want to delete this page?',
    ],

    'messages' => [
        'no_pages_found' => 'No pages found.',
        'no_pages_description' => 'Please create a new page.',
        'total_pages' => 'Total: :total items',
        'per_page' => 'Per Page',
        'create_confirmation_message' => 'Do you want to create a page with this content?',
        'update_confirmation_message' => 'Do you want to update the page with this content?',
    ],

    'validation' => [
        'title_required' => 'Title is required.',
        'title_max' => 'Title must not exceed 255 characters.',
        'slug_format' => 'Slug may only contain lowercase letters, numbers, and hyphens.',
        'slug_unique' => 'This slug is already taken.',
        'content_required' => 'Content is required.',
        'status_required' => 'Status is required.',
        'published_at_date' => 'Published at must be a valid date.',
        'published_at_future' => 'Published at must be a date in the future or present.',
    ],
];
