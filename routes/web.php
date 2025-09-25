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
use Plugins\PagesPlugin\App\Http\Controllers\Admin\PagesPluginAdminPagesController;
use App\Models\SecuritySetting;
use Plugins\PagesPlugin\App\Models\Page;

//個別ページ
Route::middleware(['front.ip'])->group(
    function () {
        Route::get(config('custom.pages_directory') . '/{slug}', function ($slug) {
            $page = Page::where('slug', $slug)->firstOrFail();
            return view('default/pages', compact('page'));
        });
    }
);
