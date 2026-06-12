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
    protected $table = 'plg_dixlase_pages';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // pages table — final post-beta schema.
        //
        // History notes for the earlier migrations folded into this one
        // (00003 consolidate_content_columns, 00004 remove_seo_columns,
        //  00006 widen_content_columns_to_longtext,
        //  00007 add_parent_id_to_dixlase_pages_table):
        //
        // - The legacy per-editor columns (content_markdown / content_html /
        //   content_blade) collapsed into a single `content` column; the
        //   editor_type column drives which dialect that content is stored
        //   in. Drop happened in the pre-folded 00003.
        // - meta_description / ogp_image_id moved out to the DixlaseSEO
        //   plugin's own table once the seo-meta capability landed.
        //   Removed in the pre-folded 00004.
        // - text → longText for content / custom_css / custom_js so 64 KiB
        //   of HTML, inline SVG, or Japanese copy does not surface as
        //   1366 "Incorrect string value". Applied in the pre-folded 00006.
        // - parent_id + (parent_id, slug, lang, deleted_at) unique +
        //   parent_id index for the hierarchical URL feature. Added in
        //   the pre-folded 00007.
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            $table->string('slug'); // Page URL (slug)
            $table->string('lang', 10)->comment('言語コード'); // Language code
            $table->string('title')->nullable(); // Title
            // longText (4 GiB) instead of text (64 KiB): rendered HTML, Blade
            // sources, and asset bundles routinely exceed 64 KiB, and a TEXT
            // column would surface the truncation as a 1366 "Incorrect string
            // value" rather than a clean size error.
            $table->longText('content')->nullable(); // Content (general purpose; dialect = editor_type)
            $table->longText('custom_css')->nullable(); // Custom CSS
            $table->longText('custom_js')->nullable(); // Custom JavaScript
            $table->tinyInteger('storage_type')->default(0); // Storage method (0=database, 1=file)
            $table->tinyInteger('editor_type')->default(3); // Editor type (1=gui, 2=markdown, 3=html, 4=blade)
            $table->tinyInteger('status')->default(0); // Status (0=draft, 1=public, 2=scheduled)
            $table->timestamp('published_at')->nullable(); // Publication date and time
            $table->timestamps();
            $table->softDeletes();

            // Slug uniqueness is scoped by parent_id so the same leaf slug
            // can appear under different parents but never as siblings.
            // MySQL treats NULLs as not-equal in unique indexes, so the
            // DB layer does not enforce uniqueness for top-level pages
            // (parent_id IS NULL); the UniqueContentSlug application rule
            // covers that case.
            $table->unique(
                ['parent_id', 'slug', 'lang', 'deleted_at'],
                'plg_dixlase_pages_parent_slug_lang_del_unique'
            );
            $table->index('lang');
            $table->index('parent_id', 'plg_dixlase_pages_parent_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->table);
    }
};
