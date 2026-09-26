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
        $asset = $this->resolveContent($this->resolvePath($request), 'js');

        if ($asset === null) {
            abort(404);
        }

        return $this->buildResponse($asset['content'], 'application/javascript', $asset['published']);
    }

    /**
     * Serve custom CSS as an external file
     */
    public function style(Request $request): Response
    {
        $asset = $this->resolveContent($this->resolvePath($request), 'css');

        if ($asset === null) {
            abort(404);
        }

        return $this->buildResponse($asset['content'], 'text/css', $asset['published']);
    }

    /**
     * Read the URL path from the route by name. Necessary because Laravel
     * binds scalar controller parameters by position, and the
     * locale-prefixed mirror route adds a {locale} parameter ahead of
     * {path}.
     */
    private function resolvePath(Request $request): string
    {
        return (string) ($request->route('path') ?? '');
    }

    /**
     * Resolve JS or CSS content from the page identified by the
     * hierarchical URL path. Mirrors the page-display route's visibility
     * rules: admins (logged-in members) can fetch assets for draft pages
     * so the admin preview iframe paints styled. Anonymous visitors only
     * see published / past-scheduled pages. Without the admin branch the
     * page HTML loads (the page route has its own admin bypass) but the
     * asset routes return 404, leaving the preview unstyled.
     */
    /**
     * @return array{content: string, published: bool}|null
     */
    private function resolveContent(string $path, string $type): ?array
    {
        $locale = App::getLocale();

        $page = DixlasePagesPage::resolvePath(
            $path,
            $locale,
            publishedOnly: ! Auth::guard('member')->check()
        );

        if ($page === null) {
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

        return ['content' => $content, 'published' => $page->isPublished()];
    }

    /**
     * Build a cacheable response with proper headers
     */
    private function buildResponse(string $content, string $contentType, bool $published): Response
    {
        $etag = '"'.md5($content).'"';

        return response($content, 200, [
            'Content-Type' => $contentType.'; charset=UTF-8',
            // A draft's assets are served only to logged-in members; a shared
            // cache must never keep them and hand them to anonymous visitors.
            'Cache-Control' => $published ? 'public, max-age=3600' : 'private, no-store',
            'ETag' => $etag,
        ]);
    }
}
