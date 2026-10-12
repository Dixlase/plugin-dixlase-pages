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
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Tests\TestCase;

/**
 * Regression test for the admin page detail route.
 *
 * `/admin/pages/{page}` used to call `view('...admin.pages.show', ...)`
 * for a view that does not exist, so a direct hit of the URL 500'd on
 * `View [admin.pages.show] not found`. The action now redirects to the
 * `edit` route (clicking a page from the index already goes there), so
 * this test asserts the redirect holds and the show route no longer
 * returns a 5xx.
 */
class AdminPagesShowRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        // Pin a known-good 32-byte AES-256-CBC key: the plugin CI workflow
        // injects an APP_KEY whose decoded length is not 32 bytes, which
        // AES-256-CBC refuses with "Unsupported cipher or incorrect key
        // length" the first time actingAs() writes to the session.
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

        $this->app->register(\Plugins\DixlasePages\App\Providers\DixlasePagesServiceProvider::class);

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
    }

    protected function tearDown(): void
    {
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_show_redirects_to_edit(): void
    {
        $admin = Member::factory()->create(['role' => MemberRole::ADMIN]);
        $page = DixlasePagesPage::factory()->create();

        $response = $this->actingAs($admin, 'member')->get("/admin/pages/{$page->id}");

        $response->assertRedirect(route('dixlase-pages::admin.pages.edit', $page));
    }
}
