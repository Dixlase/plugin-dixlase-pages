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

use App\Services\Auth\SignedInMemberResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Plugins\DixlasePages\App\Services\DixlasePagesPageRenderer;

/**
 * Serves one page at its own URL.
 *
 * Thin by design: the preparation lives in DixlasePagesPageRenderer so
 * that a caller which is not a route can reuse it. What stays here is what
 * only a route has — the 404, and the cache header on an unpublished page.
 */
class DixlasePagesPageController extends Controller
{
    public function __construct(
        private readonly DixlasePagesPageRenderer $renderer,
        private readonly SignedInMemberResolver $members,
    ) {}

    /**
     * Display the page addressed by the hierarchical URL path.
     */
    public function show(Request $request): Response
    {
        // auth('member') cannot answer this on a front request: the admin
        // login lives in the member guard's own session row, so core's
        // SignedInMemberResolver is asked instead.
        $rendered = $this->renderer->renderPath($this->resolvePath($request), $this->members->resolve());

        if ($rendered === null) {
            abort(404);
        }

        $response = response()->view('dixlase-pages::front.page', $rendered->toViewData());

        // An unpublished page is served only to a signed-in member; a
        // shared cache must never keep it and hand it to a visitor.
        if (! $rendered->storable) {
            $response->header('Cache-Control', 'private, no-store');
        }

        return $response;
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
}
