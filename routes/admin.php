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
| Plugin Admin Panel Routes (Auto-loaded)
|--------------------------------------------------------------------------
|
| This file is automatically loaded by PluginServiceProvider when the plugin
| is enabled. The following middleware are automatically applied:
|
| - admin.ip: IP address filtering
| - auth:member: Admin member authentication
| - verified: Email verification check
| - log.admin.activity: Admin panel operation log
|
| Route prefix: /admin (dynamically retrieved)
| Route name: Fully controlled by plugin (e.g., dixlase-pages::admin.pages.index)
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

        // Revisions (view, diff, restore)
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
