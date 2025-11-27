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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $table = 'plg_dixlase_page_translations';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // page_translationsテーブルを作成
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('page_id');
            $table->string('locale', 10);
            $table->string('title')->nullable(); // タイトル（少なくとも1言語は必須）
            $table->text('content')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('ogp_image')->nullable(); // 旧形式（互換性のため残す）
            $table->unsignedBigInteger('ogp_image_id')->nullable();
            $table->timestamps();

            // インデックス
            $table->unique(['page_id', 'locale']);
            $table->index('locale');

            // 外部キー制約
            $table->foreign('page_id')
                ->references('id')
                ->on('plg_dixlase_pages')
                ->onDelete('cascade');
                
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
