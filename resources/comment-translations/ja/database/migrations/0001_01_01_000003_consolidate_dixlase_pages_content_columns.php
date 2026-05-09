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
 * Content column consolidation migration
 *
 * Consolidates the three columns content_markdown, content_html, and content_blade
 * into a single content column.
 * Migrates values from the old columns to content according to editor_type, then removes the old columns.
 */' => '/**
 * コンテンツカラム統合マイグレーション
 *
 * content_markdown, content_html, content_blade の3カラムを
 * 単一の content カラムに統合する。
 * editor_type に応じた旧カラムの値を content に移行後、旧カラムを削除する。
 */',
    '// Consolidate existing data: migrate content_* column values to content according to editor_type' => '// 既存データの統合: editor_type に応じた content_* カラムの値を content に移行',
    '// If content_* is null, use the original content as-is' => '// content_* が null なら元の content をそのまま使う',
    '// Remove old columns' => '// 旧カラムを削除',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '/**
 * Content column consolidation migration
 *
 * Consolidates the three columns content_markdown, content_html, and content_blade
 * into a single content column.
 * Migrates values from the old columns to content according to editor_type, then removes the old columns.
 */' => 'machine',
        '// Consolidate existing data: migrate content_* column values to content according to editor_type' => 'machine',
        '// If content_* is null, use the original content as-is' => 'machine',
        '// Remove old columns' => 'machine',
    ],
];
