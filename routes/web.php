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
        // Use Request->route('slug') instead of a positional closure parameter:
        // when this route is mirrored under the {locale} prefix, Laravel binds
        // the closure's first scalar argument to the {locale} value, not {slug}.
        Route::get($pagesDirectory.'/{slug}', function (\Illuminate\Http\Request $request) {
            $slug = (string) ($request->route('slug') ?? '');
            $locale = app()->getLocale();

            // Look up the page row. Two storage patterns are supported:
            //
            //   1. Legacy "one row per language" — distinct rows with the
            //      same slug but different `lang` values (the migration's
            //      unique constraint covers (slug, lang, deleted_at)).
            //      forLang($locale) finds the right row directly.
            //
            //   2. DixlaseMultilingual translation overlay — a single row
            //      with `lang` set to the site's primary locale plus
            //      per-locale overlay rows in
            //      plg_dixlase_multilingual_translations. forLang($locale)
            //      returns nothing for non-primary locales, so we fall
            //      back to the site's primary locale and let the
            //      TranslatableTrait inside getContentByEditorType()
            //      surface the right translation at render time.
            //
            // Both patterns can coexist: a slug that has its own
            // lang=$locale row uses it (pattern 1 wins for that locale);
            // otherwise the primary-locale row + multilingual overlay
            // takes over (pattern 2).
            $baseQuery = DixlasePagesPage::where('slug', $slug);

            // Preview unpublished pages only when a logged-in admin is
            // viewing them. Public visitors see published / scheduled rows.
            if (! auth('member')->check()) {
                $baseQuery->published();
            }

            $page = (clone $baseQuery)->forLang($locale)->first();

            if ($page === null) {
                $primaryLocale = \App\Helpers\LocaleHelper::getSiteDefaultLocale();
                if ($primaryLocale !== $locale && $primaryLocale !== '') {
                    $page = (clone $baseQuery)->forLang($primaryLocale)->first();
                }
            }

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
        })->name('dixlase-pages::page.show');
    }
);
