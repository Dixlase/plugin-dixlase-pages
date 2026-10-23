<?php

/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

use App\Support\Content\FileContentColumns;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $table = 'plg_dixlase_pages';

    /**
     * Run the migrations.
     *
     * Adds the hashes core's FileContentPublisher keeps to publish file-stored
     * pages through the admin (#57). Existing rows keep NULL: the published
     * hash is computed from the columns until the first check stores it, and
     * the files are compared with the columns, so a file already changed
     * outside the admin is not taken as published.
     *
     * Added as a new migration because released versions have already run
     * the create migration. Idempotent.
     */
    public function up(): void
    {
        if (! Schema::hasTable($this->table) || Schema::hasColumn($this->table, FileContentColumns::PUBLISHED_HASH)) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            FileContentColumns::add($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable($this->table) && Schema::hasColumn($this->table, FileContentColumns::PUBLISHED_HASH)) {
            Schema::table($this->table, function (Blueprint $table) {
                FileContentColumns::drop($table);
            });
        }
    }
};
