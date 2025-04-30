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
use Plugins\PagesPlugin\App\Http\Controllers\Admin\PagesPluginAdminPagesController;
use App\Models\SecuritySetting;
use Plugins\PagesPlugin\App\Models\Page;

// 管理画面のルート。
$adminUrl = SecuritySetting::get('admin_url', config('security.admin_url'));

Route::prefix($adminUrl)
    ->group(
        function () {
            // 管理画面
            Route::middleware(['plugin', 'auth:member', 'admin.ip'])
                ->name('pages-plugin::admin.')
                ->group(
                    function () {
                        // イベント一覧
                        Route::get('/pages', [PagesPluginAdminPagesController::class, 'index'])->name('pages.index');
                        // イベント作成
                        Route::get('/pages/create', [PagesPluginAdminPagesController::class, 'create'])->name('pages.create');
                        // イベント保存
                        Route::post('/pages/store', [PagesPluginAdminPagesController::class, 'store'])->name('pages.store');
                        // ページ編集
                        Route::get('/pages/edit/{page}', [PagesPluginAdminPagesController::class, 'edit'])->name('pages.edit');
                        // ページ削除
                        Route::delete('/pages/delete/{page}', [PagesPluginAdminPagesController::class, 'destroy'])->name('pages.destroy');
                    }
                );
        }
    );
