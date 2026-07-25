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
    'title_required' => 'Title is required.',
    'title_max' => 'Title must not exceed 255 characters.',
    'slug_format' => 'Slug may only contain lowercase letters, numbers, and hyphens.',
    'slug_unique' => 'This slug is already taken.',
    'content_required' => 'Content is required.',
    'status_required' => 'Status is required.',
    'published_at_required' => 'Published date is required when status is set to scheduled.',
    'published_at_date' => 'Published at must be a valid date.',
    'published_at_future' => 'Published at must be a date in the future or present.',
    'route_slug_required' => 'URL slug is required.',
    'route_slug_alpha_dash' => 'URL slug may only contain letters, numbers, hyphens, and underscores.',
    'default_status_required' => 'Default status is required.',
    'default_status_in' => 'Default status must be published, draft, or scheduled.',
    'at_least_one_title_required' => 'Title is required.',
    'parent_self' => 'A page cannot be its own parent.',
    'parent_cycle' => 'The parent page cannot be one of this page\'s descendants.',
    'parent_depth_exceeded' => 'Page hierarchy is limited to 3 levels.',
];
