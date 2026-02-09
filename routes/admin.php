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

use Illuminate\Support\Facades\Route;
use Plugins\DixlasePages\App\Http\Controllers\Admin\DixlasePagesAdminPagesController;

/*
|--------------------------------------------------------------------------
| プラグイン管理画面ルート（自動読み込み）
|--------------------------------------------------------------------------
|
| このファイルはプラグインが有効化されている場合、PluginServiceProviderによって
| 自動的に読み込まれます。以下のミドルウェアが自動適用されます：
|
| - admin.ip: IPアドレスフィルタリング
| - auth:member: 管理メンバー認証
| - verified: メール認証済みチェック
| - log.admin.activity: 管理画面操作ログ
|
| ルートプレフィックス: /admin（動的に取得）
| ルート名: プラグイン側で完全に制御（例: dixlase-pages::admin.pages.index）
|
*/

Route::prefix('pages')
    ->name('dixlase-pages::admin.pages.')
    ->group(function () {
        Route::get('/', [DixlasePagesAdminPagesController::class, 'index'])->name('index');
        Route::get('/new', [DixlasePagesAdminPagesController::class, 'create'])->name('create');
        Route::get('/settings', [DixlasePagesAdminPagesController::class, 'settings'])->name('settings');
        Route::post('/settings', [DixlasePagesAdminPagesController::class, 'updateSettings'])->name('settings.update');
        Route::post('/', [DixlasePagesAdminPagesController::class, 'store'])->name('store');
        Route::get('/{page}', [DixlasePagesAdminPagesController::class, 'show'])->name('show');
        Route::get('/{page}/edit', [DixlasePagesAdminPagesController::class, 'edit'])->name('edit');
        Route::patch('/{page}', [DixlasePagesAdminPagesController::class, 'update'])->name('update');
        Route::delete('/{page}', [DixlasePagesAdminPagesController::class, 'destroy'])->name('destroy');
    });
