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

namespace Plugins\DixlasePages\Tests\Feature\Admin;

use App\Enums\MemberRole;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Models\DixlasePagesPageRevision;
use Tests\TestCase;

/**
 * Admin GET smoke test.
 *
 * Opens every admin GET route this plugin registers and asserts the
 * server does not return 5xx, for both the admin and the editor role.
 *
 *   - **In scope**: status < 500 for every admin GET route under
 *     `dixlase-pages::admin.pages.*` for admin and editor roles.
 *   - **Out of scope**: 200 vs 403 role boundaries, write flows,
 *     MySQL vs SQLite behaviour differences, CSP / inline-handler
 *     scanning.
 *
 * Pages has the broadest admin surface (list / create / trash /
 * settings / show / edit / preview / revisions index / revision show
 * / preview-frame), so this is where the smoke test earns most of
 * its value.
 */
class AdminGetRoutesSmokeTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE_NAME_PREFIX = 'dixlase-pages::admin.pages.';

    private Member $admin;

    private Member $editor;

    private DixlasePagesPage $page;

    private DixlasePagesPageRevision $revision;

    protected function setUp(): void
    {
        parent::setUp();

        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        // Pin a known-good 32-byte AES-256-CBC key (see Inquiry / Cookie /
        // SEO / Multilingual smoke tests for the full rationale).
        $rawKey = random_bytes(32);
        config([
            'app.key' => 'base64:'.base64_encode($rawKey),
            'app.cipher' => 'AES-256-CBC',
        ]);
        $this->app->instance('encrypter', new \Illuminate\Encryption\Encrypter($rawKey, 'AES-256-CBC'));

        Artisan::call('migrate', [
            '--path' => 'plugins/DixlasePages/database/migrations',
            '--realpath' => false,
        ]);

        // Register the plugin's own ServiceProvider so interface bindings
        // and view / translator namespaces are in place for the real
        // request lifecycle. PluginServiceProvider does this in production;
        // the test harness does not.
        $this->app->register(\Plugins\DixlasePages\App\Providers\DixlasePagesServiceProvider::class);

        // Core's AppServiceProvider registers these namespaces at boot, but
        // boot runs with INSTALLED=false in the plugin test harness so the
        // `components` / `admin` / `layouts` / `themes` namespaces are not
        // populated by the time this setUp runs. Re-register them here.
        // The `admin/pages/index` view uses `@include('components::...')`
        // and `preview-frame` extends `themes::layouts.app`; without these
        // the smoke test would see false 500s that are a test-env gap
        // rather than a product defect it is trying to find.
        $this->app['view']->addNamespace('components', base_path('resources/views/components'));
        $this->app['view']->addNamespace('admin', base_path('resources/views/admin'));
        $this->app['view']->addNamespace('layouts', base_path('resources/views/layouts'));
        $this->app['view']->addNamespace('themes', __DIR__.'/../../stubs/theme');

        $adminUrl = (string) config('admin.admin_url', 'admin');
        $router = app('router');
        $router->group([
            'prefix' => $adminUrl,
            'middleware' => ['web', 'auth:member'],
        ], function () {
            include base_path('plugins/DixlasePages/routes/admin.php');
        });
        $router->getRoutes()->refreshNameLookups();
        $router->getRoutes()->refreshActionLookups();

        $this->admin = Member::factory()->create(['role' => MemberRole::ADMIN]);
        $this->editor = Member::factory()->create(['role' => MemberRole::EDITOR]);
        $this->page = DixlasePagesPage::factory()->create();
        $this->revision = DixlasePagesPageRevision::create([
            'page_id' => $this->page->id,
            'snapshot' => ['title' => 'rev', 'content' => '<p>rev</p>'],
            'type' => 'auto',
            'note' => null,
            'is_protected' => false,
            'created_by' => $this->admin->id,
        ]);
    }

    protected function tearDown(): void
    {
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_no_admin_get_route_returns_5xx_for_admin_role(): void
    {
        $urls = $this->adminGetRouteUrls();

        foreach ($urls as $name => $url) {
            $response = $this->actingAs($this->admin, 'member')->get($url);

            $this->assertLessThan(
                500,
                $response->status(),
                "GET {$url} ({$name}) returned {$response->status()} as admin",
            );
        }
    }

    public function test_no_admin_get_route_returns_5xx_for_editor_role(): void
    {
        $urls = $this->adminGetRouteUrls();

        foreach ($urls as $name => $url) {
            $response = $this->actingAs($this->editor, 'member')->get($url);

            $this->assertLessThan(
                500,
                $response->status(),
                "GET {$url} ({$name}) returned {$response->status()} as editor",
            );
        }
    }

    /**
     * Discover every admin GET route and fill its placeholders.
     *
     * `{page}` is the Page id; `{id}` is the PageRevision id (and is
     * constrained to numeric by `->whereNumber('id')` in the route
     * definition, so a string value would miss the route entirely).
     *
     * @return array<string, string>  Map of route name to request URL.
     */
    private function adminGetRouteUrls(): array
    {
        $urls = [];
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if ($name === null || ! str_starts_with($name, self::ROUTE_NAME_PREFIX)) {
                continue;
            }
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = $route->uri();
            $uri = str_replace('{page}', (string) $this->page->id, $uri);
            $uri = str_replace('{id}', (string) $this->revision->id, $uri);
            $urls[$name] = '/'.ltrim($uri, '/');
        }

        $this->assertNotEmpty(
            $urls,
            'No admin GET routes were discovered; did routes/admin.php load correctly in setUp?',
        );

        return $urls;
    }
}
