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

namespace Plugins\DixlasePages\App\Services;

use App\Models\Member;
use App\Services\PermissionRegistry;
use Illuminate\Support\Facades\Route;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;

/**
 * Prepares one page for the front, independently of the route.
 *
 * This was the body of the page-display route's closure. It is a class so
 * that something other than the route can render a page: the page route
 * calls it through DixlasePagesPageController, and a front-page body
 * provider can call it without an HTTP response to put the result in.
 *
 * The member is a parameter rather than something this class resolves,
 * because the caller knows which request it is answering. Passing null
 * means "treat this as a public visitor", which is the safe direction: an
 * unpublished page is then not found at all.
 */
final class DixlasePagesPageRenderer
{
    public function __construct(private readonly DixlasePagesPageContentService $contentService) {}

    /**
     * Resolve a hierarchical URL path and prepare the page behind it.
     *
     * Returns null when no page answers the path, which is how a caller
     * declines: the route turns it into a 404, and a body provider falls
     * back to whatever the theme would otherwise show.
     *
     * Two storage patterns are supported:
     *
     *   1. Legacy "one row per language" — distinct rows per (slug, lang).
     *      resolvePath() tries the current locale first via forLang($locale).
     *
     *   2. Translation overlay (TranslationResolver) — a single row at the
     *      site's primary locale plus per-locale overlays. resolvePath()
     *      falls back to the primary locale when forLang($locale) misses,
     *      and the TranslatableTrait inside getContentByEditorType()
     *      surfaces the right translation at render time.
     *
     * @param  ?Member  $member  The member signed in to the admin, if any.
     *                           A member sees drafts and future-scheduled
     *                           pages so the admin preview works; a visitor
     *                           only gets published rows.
     * @param  ?string  $locale  Defaults to the application's locale.
     */
    public function renderPath(string $path, ?Member $member, ?string $locale = null): ?DixlasePagesRenderedPage
    {
        $locale ??= app()->getLocale();

        $page = DixlasePagesPage::resolvePath(
            $path,
            $locale,
            publishedOnly: $member === null
        );

        if ($page === null) {
            return null;
        }

        return $this->render($page, $member, $locale);
    }

    /**
     * Prepare a page that has already been resolved.
     *
     * @param  ?Member  $member  Decides only whether the admin edit link is
     *                           offered. Visibility was settled when the
     *                           page was resolved, so this method renders
     *                           whatever it is given.
     * @param  ?string  $locale  Defaults to the application's locale.
     */
    public function render(DixlasePagesPage $page, ?Member $member, ?string $locale = null): DixlasePagesRenderedPage
    {
        $locale ??= app()->getLocale();

        $editorType = $page->editor_type->slug() ?? 'html';
        $content = $page->getContentByEditorType() ?? '';

        // GUI editor: render JSON content to HTML for front display.
        // EditorManager is resolved here rather than injected, so a page
        // that is not GUI-authored never builds it — which is what the
        // route's closure did.
        if ($editorType === 'gui' && $content) {
            $renderedHtml = app(\App\Services\Editor\EditorManager::class)->renderContent('gui', $content);
            if ($renderedHtml !== '') {
                $content = $renderedHtml;
                $editorType = 'html';
            }
        }

        return new DixlasePagesRenderedPage(
            page: $page,
            editorType: $editorType,
            content: $content,
            hasCustomCss: ! empty($this->contentService->getCssContent($page, $locale)),
            hasCustomJs: ! empty($this->contentService->getJsContent($page, $locale)),
            customAssetVersion: $page->updated_at?->timestamp ?? time(),
            adminEditUrl: $this->adminEditUrl($page, $member),
            storable: $page->isPublished(),
        );
    }

    /**
     * The page's edit screen, for core's admin bar, when the member signed
     * in to the admin may open it (#109). The plugin's role settings, with
     * a site's overrides, decide, as they do for the edit route itself. No
     * link when the admin routes are not loaded.
     */
    private function adminEditUrl(DixlasePagesPage $page, ?Member $member): ?string
    {
        $role = $member?->getMemberRole();

        return $role !== null
            && Route::has('dixlase-pages::admin.pages.edit')
            && PermissionRegistry::canAccessPlugin('DixlasePages', 'pages.edit', $role)
            ? route('dixlase-pages::admin.pages.edit', ['page' => $page->id])
            : null;
    }
}
