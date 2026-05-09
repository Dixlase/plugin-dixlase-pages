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
use Plugins\DixlasePages\App\Http\Controllers\Front\DixlasePagesCustomAssetController;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Models\DixlasePagesPageSetting;

// Individual pages
Route::middleware(['front.ip'])->group(
    function () {
        // Get settings from database, or use default values if none exist
        $pagesDirectory = DixlasePagesPageSetting::getValue('route_slug', 'pages');

        // Custom CSS/JS asset routes (defined before page display routes)
        Route::get($pagesDirectory.'/{slug}/custom-style.css', [DixlasePagesCustomAssetController::class, 'style'])
            ->name('dixlase-pages::page.custom-style')
            ->withoutMiddleware([\App\Http\Middleware\ContentSecurityPolicy::class]);

        Route::get($pagesDirectory.'/{slug}/custom-script.js', [DixlasePagesCustomAssetController::class, 'script'])
            ->name('dixlase-pages::page.custom-script')
            ->withoutMiddleware([\App\Http\Middleware\ContentSecurityPolicy::class]);

        // Page display routes
        Route::get($pagesDirectory.'/{slug}', function ($slug) {
            $locale = app()->getLocale();

            // Show all pages if logged in to admin panel (preview feature)
            if (auth('member')->check()) {
                $page = DixlasePagesPage::where('slug', $slug)
                    ->forLang($locale)
                    ->firstOrFail();
            } else {
                // Only public pages if not logged in
                // (published or scheduled with publication date in the past)
                $page = DixlasePagesPage::where('slug', $slug)
                    ->forLang($locale)
                    ->published()
                    ->firstOrFail();
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
        })->name('dixlase-pages::page.show');
    }
);
