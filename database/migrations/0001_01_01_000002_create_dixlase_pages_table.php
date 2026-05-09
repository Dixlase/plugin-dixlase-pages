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
        // pages table
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->string('slug'); // Page URL (slug)
            $table->string('lang', 10)->comment('言語コード'); // Language code
            $table->string('title')->nullable(); // Title
            $table->text('content')->nullable(); // Content (general purpose)
            $table->text('content_markdown')->nullable(); // For Markdown editor
            $table->text('content_html')->nullable(); // For HTML editor
            $table->text('content_blade')->nullable(); // For Blade editor
            $table->text('custom_css')->nullable(); // Custom CSS
            $table->text('custom_js')->nullable(); // Custom JavaScript
            $table->text('meta_description')->nullable(); // Meta description
            $table->unsignedBigInteger('ogp_image_id')->nullable(); // OGP image
            $table->tinyInteger('storage_type')->default(0); // Storage method (0=database, 1=file)
            $table->tinyInteger('editor_type')->default(3); // Editor type (1=gui, 2=markdown, 3=html, 4=blade)
            $table->tinyInteger('status')->default(0); // Status (0=draft, 1=public, 2=scheduled)
            $table->timestamp('published_at')->nullable(); // Publication date and time
            $table->timestamps();
            $table->softDeletes();
            
            // Unique constraint for soft delete support (slug + lang + deleted_at)
            $table->unique(['slug', 'lang', 'deleted_at'], 'plg_dixlase_pages_slug_lang_del_unique');
            $table->index('lang');
            $table->index('ogp_image_id');
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
