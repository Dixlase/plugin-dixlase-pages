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

use Illuminate\Support\Facades\Route;
use Plugins\DixlasePages\App\Http\Controllers\Admin\DixlasePagesAdminPagesController;

/*
|--------------------------------------------------------------------------
| DixlasePages Admin Routes
|--------------------------------------------------------------------------
|
| 管理画面用のルートを定義します。
| これらのルートは管理画面のミドルウェアが適用されます。
|
*/

Route::prefix('pages')
    ->name('dixlase-pages::admin.pages.')
    ->middleware(['admin.ip']) // IPアドレスフィルタを適用
    ->group(function () {
        // 認証チェックを各ルートで実行
        Route::middleware(['auth:member'])->group(function () {
            Route::get('/', [DixlasePagesAdminPagesController::class, 'index'])->name('index');
            Route::get('/create', [DixlasePagesAdminPagesController::class, 'create'])->name('create');
            Route::get('/settings', [DixlasePagesAdminPagesController::class, 'settings'])->name('settings');
            Route::post('/settings', [DixlasePagesAdminPagesController::class, 'updateSettings'])->name('settings.update');
            Route::post('/', [DixlasePagesAdminPagesController::class, 'store'])->name('store');
            Route::get('/{page}', [DixlasePagesAdminPagesController::class, 'show'])->name('show');
            Route::get('/{page}/edit', [DixlasePagesAdminPagesController::class, 'edit'])->name('edit');
            Route::patch('/{page}', [DixlasePagesAdminPagesController::class, 'update'])->name('update');
            Route::delete('/{page}', [DixlasePagesAdminPagesController::class, 'destroy'])->name('destroy');
        });
    });
