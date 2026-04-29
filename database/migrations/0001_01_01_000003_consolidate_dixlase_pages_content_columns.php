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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * コンテンツカラム統合マイグレーション
 *
 * content_markdown, content_html, content_blade の3カラムを
 * 単一の content カラムに統合する。
 * editor_type に応じた旧カラムの値を content に移行後、旧カラムを削除する。
 */
return new class extends Migration
{
    protected $table = 'plg_dixlase_pages';

    public function up(): void
    {
        // 既存データの統合: editor_type に応じた content_* カラムの値を content に移行
        DB::table($this->table)->whereNull('deleted_at')->orderBy('id')->chunk(100, function ($pages) {
            foreach ($pages as $page) {
                $content = match ($page->editor_type) {
                    'markdown' => $page->content_markdown,
                    'html' => $page->content_html,
                    'blade' => $page->content_blade,
                    default => $page->content,
                };

                // content_* が null なら元の content をそのまま使う
                if ($content === null) {
                    $content = $page->content;
                }

                DB::table($this->table)->where('id', $page->id)->update(['content' => $content]);
            }
        });

        // 旧カラムを削除
        Schema::table($this->table, function (Blueprint $table) {
            $table->dropColumn(['content_markdown', 'content_html', 'content_blade']);
        });
    }

    public function down(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->text('content_markdown')->nullable()->after('content');
            $table->text('content_html')->nullable()->after('content_markdown');
            $table->text('content_blade')->nullable()->after('content_html');
        });
    }
};
