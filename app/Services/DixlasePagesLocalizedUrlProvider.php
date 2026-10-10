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

use App\Contracts\I18n\LocalizedUrlProvider;
use App\Helpers\LocaleHelper;
use Illuminate\Http\Request;

/**
 * The same Pages page in every supported locale, for hreflang links.
 *
 * Answers only for the front page route (dixlase-pages::page.show), and
 * only on the /{locale} copy of it that DixlaseMultilingual mounts. Admin
 * and custom CSS / JS routes get nothing. Each alternate is the current
 * route with another locale; the page path is shared across languages,
 * and DixlaseMultilingual keeps only the enabled locales.
 *
 * Every locale, the default one included, gets a /{locale} URL, as in
 * DixlaseBlog. Dixlase/plugin-dixlase-multilingual#43 settles the
 * default-locale convention for hreflang; follow it there.
 */
class DixlasePagesLocalizedUrlProvider implements LocalizedUrlProvider
{
    private const PAGE_ROUTE = 'dixlase-pages::page.show';

    public function getAlternateUrls(Request $request): array
    {
        $route = $request->route();

        if ($route === null || $route->getName() !== self::PAGE_ROUTE
            || ! in_array('locale', $route->parameterNames(), true)) {
            return [];
        }

        $parameters = array_diff_key($route->parameters(), ['locale' => true]);
        $query = $request->getQueryString();
        $suffix = $query !== null && $query !== '' ? '?'.$query : '';

        $urls = [];
        foreach (LocaleHelper::supportedLocales() as $locale) {
            $urls[$locale] = route(self::PAGE_ROUTE, ['locale' => $locale] + $parameters).$suffix;
        }

        return $urls;
    }
}
