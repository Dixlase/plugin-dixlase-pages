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

use App\Contracts\Front\FrontPageBodyProviderInterface;
use App\DTO\Front\FrontPageBodyContext;
use App\DTO\Front\FrontPageBodyDTO;
use App\DTO\Front\FrontPageBodyResult;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;

/**
 * Offers a published page as the body of a theme's front page (#127).
 *
 * The theme keeps its hero above and its footer below; between them it shows
 * one page of this plugin's, rendered by this plugin. Rendering here rather
 * than handing the theme the content is what lets the page's own editor type,
 * its GUI-to-HTML conversion and its custom CSS and JS apply without the
 * theme knowing any of it.
 *
 * Unlike a provider that offers a single destination, this one offers a
 * choice per published page, because the operator is picking a particular
 * page.
 *
 * **Only published pages are offered, and only a published page renders.**
 * That is deliberate and it is what keeps the result DTO free of any
 * response-header concern: an operator can never choose a draft, and a page
 * that is later unpublished makes renderFrontPageBody() decline, so the
 * theme falls back to core's front page content. Nothing unpublished is ever
 * served from here, so nothing has to ask a shared cache not to keep it.
 *
 * **The canonical URL is left alone.** The page stays reachable at its own
 * address, so the same content answers two URLs. The plugin settles that the
 * same way it settles it on its own route: through the SEO extension, which
 * emits no canonical of its own today. The body contract excludes a
 * canonical for a reason — more than one extension may already emit one for
 * a request, and a second tag is not an override but a pair of conflicting
 * tags, which is read as none at all.
 */
final class DixlasePagesFrontPageBodyProvider implements FrontPageBodyProviderInterface
{
    /**
     * This plugin's slug, as plugin.json declares it and as the SEO
     * extension keys a page's meta by.
     */
    public const PLUGIN_SLUG = 'dixlase-pages';

    public function __construct(private readonly DixlasePagesPageRenderer $renderer) {}

    public function getPluginSlug(): string
    {
        return self::PLUGIN_SLUG;
    }

    public function isCapabilityAvailable(): bool
    {
        return true;
    }

    public function getFrontPageBodyLabel(): string
    {
        return __('dixlase-pages::admin.front_page_body.label');
    }

    /**
     * Every published page, newest first.
     *
     * The query shape is the linkable provider's, which already answers
     * "which pages may be pointed at" for the menu and link choosers.
     *
     * An empty array is returned when nothing is published, which the
     * contract allows and which is the honest answer: unlike a blog top page,
     * a page that does not exist is not a destination an operator could mean.
     *
     * @return FrontPageBodyDTO[]
     */
    public function availableFrontPageBodies(int $limit = 100): array
    {
        if ($limit < 1) {
            return [];
        }

        $pages = DixlasePagesPage::published()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();

        $labels = $this->disambiguatedLabels($pages);

        return array_map(
            fn (DixlasePagesPage $page, string $label) => new FrontPageBodyDTO(
                id: (string) $page->getKey(),
                label: $label,
                description: $this->pagePath($page),
                // The path is in meta as well as in the label, because a
                // chooser that shows more than a label should not have to
                // parse it back out of one.
                meta: [
                    'path' => $this->pagePath($page),
                    'slug' => (string) $page->slug,
                    'lang' => $page->lang === null ? null : (string) $page->lang,
                ],
            ),
            $pages,
            $labels,
        );
    }

    public function renderFrontPageBody(string $bodyId, FrontPageBodyContext $context): ?FrontPageBodyResult
    {
        // The id this provider offers is a page's primary key. Anything else
        // is a stored choice from another provider, or a stale one.
        if ($bodyId === '' || ! ctype_digit($bodyId)) {
            return null;
        }

        $page = DixlasePagesPage::published()->whereKey($bodyId)->first();

        if ($page === null) {
            return null;
        }

        return $this->inLocale($context->locale, function () use ($page, $context): FrontPageBodyResult {
            // No member: this method only ever renders a published page, and
            // the member decides nothing else here but the admin edit link,
            // which a body has nowhere to put. Passing null is also the safe
            // direction — see DixlasePagesPageRenderer.
            $rendered = $this->renderer->render($page, null, $context->locale);

            $title = $page->getTranslation('title') ?: (string) $page->slug;
            $description = $this->description($page, $context->locale);

            // Hand this page's meta to the SEO extension, exactly as the
            // page's own route does, so the front page describes the page it
            // is showing instead of falling back to the site-wide defaults.
            // Positional, not named: the helper's older releases declare only
            // the first two parameters, and PHP ignores an extra positional
            // argument while an unknown named one is a fatal Error.
            if (function_exists('dls_seo_set_page_meta')) {
                dls_seo_set_page_meta($page, self::PLUGIN_SLUG, $title);
            }

            return new FrontPageBodyResult(
                content: View::make('dixlase-pages::front._page-body', [
                    'page' => $rendered->page,
                    'editorType' => $rendered->editorType,
                    'content' => $rendered->content,
                    'showHeading' => $context->showHeading,
                    // No wrapperClass: the theme has already opened its own
                    // container around the body.
                ])->render(),
                title: $title,
                description: $description,
                styleUrls: $this->customAssetUrls($rendered, 'dixlase-pages::page.custom-style', $rendered->hasCustomCss),
                scriptUrls: $this->customAssetUrls($rendered, 'dixlase-pages::page.custom-script', $rendered->hasCustomJs),
                meta: ['page_id' => (string) $page->getKey()],
            );
        });
    }

    /**
     * The page's own custom stylesheet or script, as a list of one or none.
     *
     * The URLs are keyed on the page's hierarchical path, not on the request,
     * so they keep resolving while the page is shown at `/`. That looks wrong
     * at first glance and is correct: the asset routes are the plugin's own
     * and are absolute.
     *
     * The page view pushes these onto the theme's stacks itself. A body
     * cannot push, so they travel in the result and the theme places them.
     *
     * @return array<int, string>
     */
    private function customAssetUrls(DixlasePagesRenderedPage $rendered, string $routeName, bool $has): array
    {
        if (! $has) {
            return [];
        }

        $url = route($routeName, ['path' => $this->pagePath($rendered->page)]);

        return [$url.'?v='.$rendered->customAssetVersion];
    }

    /**
     * The page's hierarchical path, as its asset routes and its own URL use.
     */
    private function pagePath(DixlasePagesPage $page): string
    {
        return implode('/', $page->pathSegments());
    }

    /**
     * The page's SEO meta description for this locale, or null.
     *
     * The per-locale translation first, then the primary value the model
     * exposes from the SEO extension's store. Both are null when that
     * extension is not installed, which is why the result's description is
     * nullable.
     */
    private function description(DixlasePagesPage $page, ?string $locale): ?string
    {
        $translated = $page->getTranslation('seo_description', $locale ?? App::getLocale(), false);

        if (is_string($translated) && $translated !== '') {
            return $translated;
        }

        $primary = $page->seo_description;

        return is_string($primary) && $primary !== '' ? $primary : null;
    }

    /**
     * One label per page, with the path appended only where it is needed.
     *
     * A theme's chooser may render nothing but the label — the OnePage theme's
     * is a `<select>` of `<option>` elements — so two pages called "About"
     * would be indistinguishable there. The DTO's description and meta carry
     * the path for a chooser that shows more; the label has to carry it too
     * for one that does not.
     *
     * Appended only on a collision, because a path on every row is noise on
     * the sites that have no collision, which is most of them. When the path
     * collides as well — the same page as a separate row per language, which
     * the legacy storage shape produces — the language is appended after it.
     *
     * @param  array<int, DixlasePagesPage>  $pages
     * @return array<int, string>
     */
    private function disambiguatedLabels(array $pages): array
    {
        $titles = array_map(
            fn (DixlasePagesPage $page) => $page->getTranslation('title') ?: (string) $page->slug,
            $pages,
        );
        $titleCounts = array_count_values($titles);

        $withPath = [];
        foreach ($pages as $i => $page) {
            $withPath[$i] = ($titleCounts[$titles[$i]] ?? 0) > 1
                ? $titles[$i].' ('.$this->pagePath($page).')'
                : $titles[$i];
        }

        $pathCounts = array_count_values($withPath);

        foreach ($pages as $i => $page) {
            if (($pathCounts[$withPath[$i]] ?? 0) > 1 && $page->lang !== null) {
                $withPath[$i] .= ' ['.$page->lang.']';
            }
        }

        return $withPath;
    }

    /**
     * Run the render under the context's locale.
     *
     * The caller states which locale the page is being built for, and the
     * page's title, its translated content and the strings around it all come
     * from the active one. In practice a theme passes the locale it is
     * already in, so this changes nothing — but a caller that passes another
     * one means it, and the locale is restored either way.
     *
     * @template T
     *
     * @param  callable(): T  $render
     * @return T
     */
    private function inLocale(?string $locale, callable $render): mixed
    {
        if ($locale === null || $locale === App::getLocale()) {
            return $render();
        }

        $previous = App::getLocale();
        App::setLocale($locale);

        try {
            return $render();
        } finally {
            App::setLocale($previous);
        }
    }
}
