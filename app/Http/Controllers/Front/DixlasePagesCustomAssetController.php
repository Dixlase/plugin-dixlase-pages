<?php

/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
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
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
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
    public function script(string $slug): Response
    {
        $content = $this->resolveContent($slug, 'js');

        if ($content === null) {
            abort(404);
        }

        return $this->buildResponse($content, 'application/javascript');
    }

    /**
     * Serve custom CSS as an external file
     */
    public function style(string $slug): Response
    {
        $content = $this->resolveContent($slug, 'css');

        if ($content === null) {
            abort(404);
        }

        return $this->buildResponse($content, 'text/css');
    }

    /**
     * Resolve JS or CSS content from the page
     */
    private function resolveContent(string $slug, string $type): ?string
    {
        $locale = App::getLocale();

        $page = DixlasePagesPage::where('slug', $slug)
            ->forLang($locale)
            ->published()
            ->first();

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
