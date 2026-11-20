<?php

/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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
use Plugins\DixlasePages\App\Http\Controllers\Front\DixlasePagesCustomAssetController;
use Plugins\DixlasePages\App\Http\Controllers\Front\DixlasePagesPageController;
use Plugins\DixlasePages\App\Models\DixlasePagesPageSetting;

// Individual pages
Route::middleware(['front.ip'])->group(
    function () {
        // Get settings from database, or use default values if none exist
        $pagesDirectory = DixlasePagesPageSetting::getValue('route_slug', 'page');

        // Custom CSS/JS asset routes (defined before page display routes).
        // {path} accepts slashes so hierarchical pages like /page/parent/child
        // get their own asset URLs; the regex constraint is what allows '/'
        // through. The trailing literal segment lets Laravel split the path
        // off the asset filename when compiling the route.
        Route::get($pagesDirectory.'/{path}/custom-style.css', [DixlasePagesCustomAssetController::class, 'style'])
            ->where('path', '.+')
            ->name('dixlase-pages::page.custom-style')
            ->withoutMiddleware([\App\Http\Middleware\ContentSecurityPolicy::class]);

        Route::get($pagesDirectory.'/{path}/custom-script.js', [DixlasePagesCustomAssetController::class, 'script'])
            ->where('path', '.+')
            ->name('dixlase-pages::page.custom-script')
            ->withoutMiddleware([\App\Http\Middleware\ContentSecurityPolicy::class]);

        // Page display route. {path} accepts the full hierarchical URL
        // (e.g. "parent/child"), which the model resolver walks segment
        // by segment against (parent_id, slug) to find the target page.
        // The action reads the path off the route by name rather than by
        // position, because the locale-prefixed mirror of this route puts
        // a {locale} parameter ahead of {path}.
        Route::get($pagesDirectory.'/{path}', [DixlasePagesPageController::class, 'show'])
            ->where('path', '.+')
            ->name('dixlase-pages::page.show');
    }
);
