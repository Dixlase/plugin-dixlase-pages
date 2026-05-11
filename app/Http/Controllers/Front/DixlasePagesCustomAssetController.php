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

namespace Plugins\DixlasePages\App\Http\Controllers\Front;

use App\Enums\ContentEditorType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Services\DixlasePagesPageContentService;

/**
 * Custom JS/CSS external file delivery controller for DixlasePages
 *
 * Serves user-defined custom JavaScript and CSS as external files
 * to ensure CSP compatibility across all modes.
 */
class DixlasePagesCustomAssetController extends Controller
{
    public function __construct(private DixlasePagesPageContentService $contentService) {}

    /**
     * Serve custom JavaScript as an external file
     */
    public function script(Request $request): Response
    {
        $content = $this->resolveContent($this->resolveSlug($request), 'js');

        if ($content === null) {
            abort(404);
        }

        return $this->buildResponse($content, 'application/javascript');
    }

    /**
     * Serve custom CSS as an external file
     */
    public function style(Request $request): Response
    {
        $content = $this->resolveContent($this->resolveSlug($request), 'css');

        if ($content === null) {
            abort(404);
        }

        return $this->buildResponse($content, 'text/css');
    }

    /**
     * Read the slug from the route by name. Necessary because Laravel binds
     * scalar controller parameters by position, and the locale-prefixed
     * mirror route adds a {locale} parameter ahead of {slug}.
     */
    private function resolveSlug(Request $request): string
    {
        return (string) ($request->route('slug') ?? '');
    }

    /**
     * Resolve JS or CSS content from the page.
     *
     * Mirrors the page-display route's visibility rules: admins (logged-in
     * members) can fetch assets for draft pages so the admin preview iframe
     * paints styled. Anonymous visitors only see published / past-scheduled
     * pages. Without the admin branch the page HTML loads (the page route
     * has its own admin bypass) but the asset routes return 404, leaving
     * the preview unstyled.
     */
    private function resolveContent(string $slug, string $type): ?string
    {
        $locale = App::getLocale();

        $query = DixlasePagesPage::where('slug', $slug)->forLang($locale);

        if (! Auth::guard('member')->check()) {
            $query->published();
        }

        $page = $query->first();

        if (! $page) {
            return null;
        }

        if ($page->editor_type !== ContentEditorType::HTML) {
            return null;
        }

        $content = $type === 'js'
            ? $this->contentService->getJsContent($page, $locale)
            : $this->contentService->getCssContent($page, $locale);

        if (empty($content)) {
            return null;
        }

        return $content;
    }

    /**
     * Build a cacheable response with proper headers
     */
    private function buildResponse(string $content, string $contentType): Response
    {
        $etag = '"'.md5($content).'"';

        return response($content, 200, [
            'Content-Type' => $contentType.'; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
            'ETag' => $etag,
        ]);
    }
}
