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
            $table->string('title')->nullable(); // タイトル
            $table->text('content')->nullable(); // コンテンツ（汎用）
            $table->text('content_markdown')->nullable(); // Markdownエディタ用
            $table->text('content_html')->nullable(); // HTMLエディタ用
            $table->text('content_blade')->nullable(); // Bladeエディタ用
            $table->text('meta_description')->nullable(); // メタディスクリプション
            $table->unsignedBigInteger('ogp_image_id')->nullable(); // OGP画像
            $table->string('storage_type', 20)->default('database'); // 保存方法（database/file）
            $table->string('editor_type', 20)->default('html'); // エディタタイプ（gui/markdown/html/blade）
            $table->enum('status', ['draft', 'published', 'scheduled'])->default('draft'); // ステータス
            $table->timestamp('published_at')->nullable(); // 公開日時
            $table->timestamps();
            $table->softDeletes();
            
            // ソフトデリート対応のユニーク制約
            $table->unique(['slug', 'deleted_at'], 'plg_dixlase_pages_slug_deleted_at_unique');
            
            // 外部キー制約
            $table->foreign('ogp_image_id')
                ->references('id')
                ->on('media')
                ->onDelete('set null');
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
