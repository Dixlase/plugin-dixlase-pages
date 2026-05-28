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

    /**
     * Intentional no-op.
     *
     * The original column type was TEXT (64 KiB), but `up()` widened to
     * LONGTEXT precisely because real pages overflow that ceiling. Asking
     * MySQL to shrink LONGTEXT back to TEXT against an active table fails
     * with SQLSTATE 22001 ("Data too long for column ...") as soon as any
     * stored row exceeds 64 KiB — which is the common case once a site
     * has any sizeable page.
     *
     * Plugin uninstall walks every migration's down() in reverse, so a
     * literal reversal here would block uninstall whenever real content
     * is present. The table itself is dropped by migration 000002's
     * `down()`, which makes column-type rollback redundant for the
     * uninstall path. For a partial single-step rollback to take effect
     * the operator would have to truncate / shorten the offending rows
     * by hand first; we deliberately do not attempt that lossy operation
     * automatically.
     */
    public function down(): void {}
};
