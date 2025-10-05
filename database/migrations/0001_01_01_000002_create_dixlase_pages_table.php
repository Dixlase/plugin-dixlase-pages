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
    /**
     * Run the migrations.
     *
     * @return void
     */

    protected $table = 'dxl_plg_dixlase_pages';

    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->string('title'); // ページのタイトル
            $table->string('slug')->unique(); // ページのURL（スラッグ）
            $table->enum('status', ['draft', 'published', 'scheduled'])->default('draft'); // ステータス（下書き、公開、日付指定）
            $table->timestamp('published_at')->nullable(); // 公開日時
            $table->text('content')->nullable(); // ページの内容
            $table->text('meta_description')->nullable(); // メタディスクリプション
            $table->string('ogp_image')->nullable(); // OGP画像のパス（旧）
            $table->unsignedBigInteger('ogp_image_id')->nullable(); // OGP画像ID（メディア）
            $table->timestamps();
            $table->softDeletes();
            
            // 外部キー制約
            $table->foreign('ogp_image_id')->references('id')->on('media')->onDelete('set null');
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
