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
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
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
        // Use Request->route('path') instead of a positional closure
        // parameter: when this route is mirrored under the {locale}
        // prefix, Laravel binds the closure's first scalar argument to
        // the {locale} value, not {path}.
        Route::get($pagesDirectory.'/{path}', function (\Illuminate\Http\Request $request) {
            $path = (string) ($request->route('path') ?? '');
            $locale = app()->getLocale();

            // Admins see drafts and future-scheduled pages so the admin
            // preview works; public visitors only get published rows.
            // Two storage patterns are supported:
            //
            //   1. Legacy "one row per language" — distinct rows per
            //      (slug, lang). resolvePath() tries the current locale
            //      first via forLang($locale).
            //
            //   2. DixlaseMultilingual translation overlay — a single
            //      row at the site's primary locale plus per-locale
            //      overlays. resolvePath() falls back to the primary
            //      locale when forLang($locale) misses, and the
            //      TranslatableTrait inside getContentByEditorType()
            //      surfaces the right translation at render time.
            $page = DixlasePagesPage::resolvePath(
                $path,
                $locale,
                publishedOnly: ! auth('member')->check()
            );

            if ($page === null) {
                abort(404);
            }

            // Prepare front view variables
            $contentService = app(\Plugins\DixlasePages\App\Services\DixlasePagesPageContentService::class);
            $editorType = $page->editor_type->slug() ?? 'html';
            $content = $page->getContentByEditorType() ?? '';

            // GUI editor: render JSON content to HTML for front display
            if ($editorType === 'gui' && $content) {
                $editorManager = app(\App\Services\Editor\EditorManager::class);
                $renderedHtml = $editorManager->renderContent('gui', $content);
                if ($renderedHtml !== '') {
                    $content = $renderedHtml;
                    $editorType = 'html';
                }
            }

            $hasCustomCss = ! empty($contentService->getCssContent($page, $locale));
            $hasCustomJs = ! empty($contentService->getJsContent($page, $locale));
            $customAssetVersion = $page->updated_at?->timestamp ?? time();

            return view('dixlase-pages::front.page', compact(
                'page',
                'editorType',
                'content',
                'hasCustomCss',
                'hasCustomJs',
                'customAssetVersion',
            ));
        })->where('path', '.+')->name('dixlase-pages::page.show');
    }
);
