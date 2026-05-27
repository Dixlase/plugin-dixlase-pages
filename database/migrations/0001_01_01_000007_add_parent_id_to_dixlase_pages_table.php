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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected string $table = 'plg_dixlase_pages';

    /**
     * Add parent_id to support hierarchical page URLs (/page/parent/child).
     *
     * Slug uniqueness becomes (parent_id, slug, lang, deleted_at). MySQL
     * treats NULLs as not-equal in unique indexes, so duplicate top-level
     * slugs are not blocked at the DB layer; the UniqueContentSlug rule
     * (scoped by parent_id at the application layer) is the source of
     * truth for that case. The DB constraint still enforces uniqueness
     * for any concrete parent_id, which is what matters for the resolver.
     *
     * No DB foreign key: the existence check runs at the application
     * layer in StorePageRequest / UpdatePageRequest, which lets the
     * orphan-on-delete behaviour be expressed as a model lifecycle hook
     * rather than ON DELETE SET NULL plumbing.
     */
    public function up(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('id');

            $table->dropUnique('plg_dixlase_pages_slug_lang_del_unique');
            $table->unique(
                ['parent_id', 'slug', 'lang', 'deleted_at'],
                'plg_dixlase_pages_parent_slug_lang_del_unique'
            );

            $table->index('parent_id', 'plg_dixlase_pages_parent_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->dropIndex('plg_dixlase_pages_parent_id_idx');
            $table->dropUnique('plg_dixlase_pages_parent_slug_lang_del_unique');
            $table->unique(['slug', 'lang', 'deleted_at'], 'plg_dixlase_pages_slug_lang_del_unique');
            $table->dropColumn('parent_id');
        });
    }
};
