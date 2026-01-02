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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURdPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

return [
    'plugin' => [
        'name' => 'Dixlase Page Management',
        'description' => 'Adds static page management functionality to your website. Features page creation, editing, deletion, SEO settings, publish/draft control, and comprehensive content management tools.',
    ],
    
    // Menu plugin integration
    'provider' => [
        'label' => 'Pages',
    ],
    
    'nav' => [
        'pages' => [
            'text' => 'Page Management',
            'index' => 'Page Master',
            'create' => 'Create Page',
            'settings' => 'Page Settings',
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
        'settings' => [
            'heading' => 'Page Settings',
        ],
        'search_section' => 'Search & Filter',  
        'search_placeholder' => 'Search by title, content, slug, description',
        'all_status' => 'All Status',
        'status_filter' => 'Status Filter',
        'list_section' => 'Page List',
        'table_label' => 'Page List Table',
    ],

    'form' => [
        'meta_description' => 'Description text',
        'meta_description_help' => 'Description text displayed in search engine results. Recommended length: 120-160 characters.',
        'ogp_image' => 'OGP Image',
        'ogp_image_help' => 'Enter the path to the image displayed when shared on social media. (e.g., /images/ogp/page-image.jpg)',
    ],

    'actions' => [
        'delete_confirm' => 'Are you sure you want to delete this page?',
    ],

    'settings' => [
        'basic' => [
            'title' => 'Basic Settings',
            'pages_directory' => 'Pages Directory',
            'pages_directory_help' => 'Directory name used in page URLs',
            'default_status' => 'Default Status',
            'status_published' => 'Published',
            'status_draft' => 'Draft',
        ],
        'features' => [
            'title' => 'Feature Settings',
            'enable_comments' => 'Enable Comments',
            'enable_comments_help' => 'Display comment functionality on pages',
            'seo_enabled' => 'Enable SEO Features',
            'seo_enabled_help' => 'Enable meta tags and SEO optimization features',
        ],
        'confirm_title' => 'Save Page Settings',
        'confirm_message' => 'Are you sure you want to save the page settings?',
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
        'pages_directory_required' => 'Pages directory is required.',
        'pages_directory_alpha_dash' => 'Pages directory may only contain letters, numbers, hyphens, and underscores.',
        'default_status_required' => 'Default status is required.',
        'default_status_in' => 'Default status must be either published or draft.',
        'at_least_one_title_required' => 'Please enter a title in at least one language.',
    ],
];
