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

namespace Plugins\DixlasePages\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Plugins\DixlasePages\App\Services\DixlasePagesLocalizedUrlProvider;
use Tests\TestCase;

/**
 * hreflang alternates for Pages: the page route in every locale, and
 * nothing for admin, asset or locale-less routes.
 *
 * Routes are registered in the order the site registers them: the
 * /{locale} copy from DixlaseMultilingual first, then the bare route under
 * the same name. Laravel resolves a shared name to the first one.
 */
class DixlasePagesLocalizedUrlProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::prefix('{locale}')->where(['locale' => 'ja|en'])->group(function () {
            $this->registerPagesRoutes();
        });
        $this->registerPagesRoutes();
        Route::get('/admin-custom/pages/{page}/edit', fn () => 'x')->name('dixlase-pages::admin.pages.edit');
        Route::getRoutes()->refreshNameLookups();
    }

    public function test_page_route_yields_the_page_in_every_locale(): void
    {
        $urls = $this->alternatesFor('/ja/page/about');

        $this->assertSame(url('/ja/page/about'), $urls['ja']);
        $this->assertSame(url('/en/page/about'), $urls['en']);
    }

    public function test_hierarchical_path_and_query_string_are_kept(): void
    {
        $urls = $this->alternatesFor('/en/page/parent/child?ref=nav');

        $this->assertSame(url('/ja/page/parent/child').'?ref=nav', $urls['ja']);
        $this->assertSame(url('/en/page/parent/child').'?ref=nav', $urls['en']);
    }

    public function test_page_route_without_locale_yields_nothing(): void
    {
        $this->assertSame([], $this->alternatesFor('/page/about'));
    }

    public function test_asset_routes_yield_nothing(): void
    {
        $this->assertSame([], $this->alternatesFor('/ja/page/about/custom-style.css'));
        $this->assertSame([], $this->alternatesFor('/ja/page/about/custom-script.js'));
    }

    public function test_admin_route_yields_nothing(): void
    {
        $this->assertSame([], $this->alternatesFor('/admin-custom/pages/1/edit'));
    }

    private function registerPagesRoutes(): void
    {
        Route::get('page/{path}/custom-style.css', fn () => 'x')->where('path', '.+')->name('dixlase-pages::page.custom-style');
        Route::get('page/{path}/custom-script.js', fn () => 'x')->where('path', '.+')->name('dixlase-pages::page.custom-script');
        Route::get('page/{path}', fn () => 'x')->where('path', '.+')->name('dixlase-pages::page.show');
    }

    /**
     * @return array<string, string>
     */
    private function alternatesFor(string $uri): array
    {
        $request = Request::create($uri);
        $request->setRouteResolver(fn () => Route::getRoutes()->match($request)->bind($request));

        return app(DixlasePagesLocalizedUrlProvider::class)->getAlternateUrls($request);
    }
}
