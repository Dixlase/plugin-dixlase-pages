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
use Plugins\DixlasePages\App\Http\Controllers\Admin\DixlasePagesAdminPageRevisionController;
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
        Route::get('/trash', [DixlasePagesAdminPagesController::class, 'trash'])->name('trash');
        Route::post('/trash/empty', [DixlasePagesAdminPagesController::class, 'emptyTrash'])->name('trash.empty');
        Route::post('/trash/{id}/restore', [DixlasePagesAdminPagesController::class, 'restore'])->whereNumber('id')->name('trash.restore');
        Route::delete('/trash/{id}', [DixlasePagesAdminPagesController::class, 'forceDestroy'])->whereNumber('id')->name('trash.force-destroy');
        Route::get('/settings', [DixlasePagesAdminPagesController::class, 'settings'])->name('settings');
        Route::post('/settings', [DixlasePagesAdminPagesController::class, 'updateSettings'])->name('settings.update');
        Route::get('/preview-frame', [DixlasePagesAdminPagesController::class, 'previewFrameNew'])->name('preview-frame-new');
        Route::post('/preview', [DixlasePagesAdminPagesController::class, 'preview'])->name('preview');
        Route::post('/preview-render', [DixlasePagesAdminPagesController::class, 'previewRender'])->name('preview-render');
        Route::post('/', [DixlasePagesAdminPagesController::class, 'store'])->name('store');
        Route::get('/{page}', [DixlasePagesAdminPagesController::class, 'show'])->name('show');
        Route::get('/{page}/edit', [DixlasePagesAdminPagesController::class, 'edit'])->name('edit');
        Route::get('/{page}/preview-frame', [DixlasePagesAdminPagesController::class, 'previewFrame'])->name('preview-frame');
        Route::patch('/{page}', [DixlasePagesAdminPagesController::class, 'update'])->name('update');
        Route::delete('/{page}', [DixlasePagesAdminPagesController::class, 'destroy'])->name('destroy');

        // リビジョン（閲覧・差分表示・復元）
        Route::get('/{page}/revisions', [DixlasePagesAdminPageRevisionController::class, 'index'])->name('revisions.index');
        Route::get('/{page}/revisions/{id}', [DixlasePagesAdminPageRevisionController::class, 'show'])->whereNumber('id')->name('revisions.show');
        Route::post('/{page}/revisions/{id}/restore', [DixlasePagesAdminPageRevisionController::class, 'restore'])
            ->whereNumber('id')
            ->name('revisions.restore');
        Route::post('/{page}/revisions/{id}/note', [DixlasePagesAdminPageRevisionController::class, 'updateNote'])
            ->whereNumber('id')
            ->name('revisions.note');
        Route::post('/{page}/revisions/{id}/protect', [DixlasePagesAdminPageRevisionController::class, 'toggleProtection'])
            ->whereNumber('id')
            ->name('revisions.protect');
    });
