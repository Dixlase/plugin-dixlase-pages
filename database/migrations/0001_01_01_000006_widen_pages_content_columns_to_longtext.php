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

/**
 * Widen content / custom_css / custom_js from TEXT to LONGTEXT.
 *
 * The original schema declared these as TEXT (max 65,535 bytes). Realistic
 * page bodies — long-form HTML pages with extensive Japanese copy and inline
 * SVG icons — easily exceed that ceiling. When MySQL truncates a TEXT value
 * mid-multibyte-character it surfaces 1366 "Incorrect string value" rather
 * than 1406 "Data too long," which masks the underlying size cause and
 * misleads operators into chasing a charset bug.
 *
 * LONGTEXT (max 4 GiB) removes the practical ceiling.
 */
return new class extends Migration
{
    protected string $table = 'plg_dixlase_pages';

    public function up(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->longText('content')->nullable()->change();
            $table->longText('custom_css')->nullable()->change();
            $table->longText('custom_js')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->text('content')->nullable()->change();
            $table->text('custom_css')->nullable()->change();
            $table->text('custom_js')->nullable()->change();
        });
    }
};
