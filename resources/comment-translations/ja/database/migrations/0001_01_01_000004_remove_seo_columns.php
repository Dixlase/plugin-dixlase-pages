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
    '/**
 * Migration to remove SEO-related columns
 *
 * SEO functionality will be developed independently as an SEO plugin in the future,
 * so meta_description and ogp_image_id are removed from DixlasePages.
 */' => '/**
 * SEO関連カラムの削除マイグレーション
 *
 * SEO機能は将来SEOプラグインとして独立開発するため、
 * DixlasePagesから meta_description と ogp_image_id を削除する。
 */',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '/**
 * Migration to remove SEO-related columns
 *
 * SEO functionality will be developed independently as an SEO plugin in the future,
 * so meta_description and ogp_image_id are removed from DixlasePages.
 */' => 'machine',
    ],
];
