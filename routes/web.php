<?php

/**
 * This file is part of MySoftware.
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
use Plugins\PagesPlugin\app\Http\Controllers\Admin\AdminPagesPluginController;
use App\Models\SettingSecurity;

$adminUrl = SettingSecurity::get('admin_url', config('security.admin_url'));
Route::prefix($adminUrl)
    ->middleware('plugin')
    ->group(
        function () {
            // 管理画面ルートグループ
            Route::middleware(['auth:member', 'admin.ip'])
                ->name('pages-plugin::admin.')
                ->group(
                    function () {
                        // イベント一覧
                        Route::get('/pages', [AdminPagesPluginController::class, 'index'])->name('pages.index');
                        // イベント作成
                        Route::get('/pages/create', [AdminPagesPluginController::class, 'create'])->name('pages.create');
                        // イベント保存
                        Route::post('/pages/store', [AdminPagesPluginController::class, 'store'])->name('pages.store');
                        // ページ編集
                        Route::get('/pages/edit/{event}', [AdminPagesPluginController::class, 'edit'])->name('pages.edit');
                        // ページ削除
                        Route::delete('/pages/delete/{event}', [AdminPagesPluginController::class, 'destroy'])->name('pages.destroy');
                    }
                );
        }
    );
