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
        // pagesテーブル
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->string('slug'); // ページのURL（スラッグ）
            $table->string('lang', 10)->comment('言語コード'); // 言語コード
            $table->string('title')->nullable(); // タイトル
            $table->text('content')->nullable(); // コンテンツ（汎用）
            $table->text('content_markdown')->nullable(); // Markdownエディタ用
            $table->text('content_html')->nullable(); // HTMLエディタ用
            $table->text('content_blade')->nullable(); // Bladeエディタ用
            $table->text('meta_description')->nullable(); // メタディスクリプション
            $table->unsignedBigInteger('ogp_image_id')->nullable(); // OGP画像
            $table->tinyInteger('storage_type')->default(0); // 保存方法（0=database, 1=file）
            $table->tinyInteger('editor_type')->default(3); // エディタタイプ（1=gui, 2=markdown, 3=html, 4=blade）
            $table->tinyInteger('status')->default(0); // ステータス（0=draft, 1=published, 2=scheduled）
            $table->timestamp('published_at')->nullable(); // 公開日時
            $table->timestamps();
            $table->softDeletes();
            
            // ソフトデリート対応のユニーク制約（slug + lang + deleted_at）
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
