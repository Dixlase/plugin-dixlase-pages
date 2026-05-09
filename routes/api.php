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

use Illuminate\Support\Facades\Route;
use Plugins\DixlasePages\App\Http\Controllers\Admin\DixlasePagesAdminPagesController;

/*
|--------------------------------------------------------------------------
| Plugin API Routes
|--------------------------------------------------------------------------
|
| API endpoints used in the admin panel
| Middleware: admin.ip, auth:member
|
*/

Route::prefix('admin/pages')
    ->middleware(['admin.ip', 'auth:member'])
    ->name('admin.pages.api.')
    ->group(function () {
        // Get file content by editor type
        Route::get('/{page}/content/{editorType}', [DixlasePagesAdminPagesController::class, 'getFileContent'])
            ->name('content');
        
        // Get content by save method and editor type
        Route::get('/{page}/content/{storageType}/{editorType}', [DixlasePagesAdminPagesController::class, 'getContent'])
            ->name('content.get');
    });
