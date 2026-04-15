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
    protected $table = 'plg_dixlase_pages_revisions';

    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')
                ->constrained('plg_dixlase_pages')
                ->cascadeOnDelete();
            // 全フィールドの完全スナップショット
            $table->json('snapshot');
            // auto: 自動保存 / manual: 手動作成 / restore_backup: 復元前バックアップ
            $table->string('type', 20)->default('auto');
            $table->string('note')->nullable();
            // 保護フラグ。true の場合は保持件数超過時の自動削除対象外
            $table->boolean('is_protected')->default(false);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('members')
                ->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['page_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }
};
