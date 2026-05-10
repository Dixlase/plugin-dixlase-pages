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

namespace Plugins\DixlasePages\App\Services;

use App\Contracts\I18n\LocalizedUrlProvider;
use App\Helpers\LocaleHelper;
use Illuminate\Http\Request;

/**
 * Pages-side LocalizedUrlProvider implementation.
 *
 * Returns the locale-prefix-swapped equivalent of the current request URL
 * for every supported locale, but only when the current request is
 * resolved to a Pages-owned route (route name starts with "dixlase-pages::").
 * For non-Pages requests it returns an empty array so the multilingual
 * plugin's LocalizedUrlAggregator can let other plugins answer.
 *
 * Phase D ships the route-name + path-swap heuristic only. A future phase
 * may consult plg_dixlase_multilingual_translations to drop locales that
 * have no published translation, so unpublished translations no longer
 * appear in &lt;link rel="alternate" hreflang&gt; tags.
 */
class DixlasePagesLocalizedUrlProvider implements LocalizedUrlProvider
{
    public function getAlternateUrls(Request $request): array
    {
        $route = $request->route();
        if ($route === null) {
            return [];
        }

        $name = (string) $route->getName();
        if ($name === '' || ! str_starts_with($name, 'dixlase-pages::')) {
            return [];
        }

        $currentPath = '/'.ltrim($request->path(), '/');
        $query = $request->getQueryString();
        $suffix = $query !== null && $query !== '' ? '?'.$query : '';

        $urls = [];
        foreach (LocaleHelper::supportedLocales() as $locale) {
            $urls[$locale] = LocaleHelper::switchLocaleUrl($currentPath, $locale).$suffix;
        }

        return $urls;
    }
}
