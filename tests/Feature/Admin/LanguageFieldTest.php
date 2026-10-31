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

use App\Contracts\I18n\LocaleUrlRouting;
use App\Enums\MemberRole;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * The page form shows the language field only when core says front URLs
 * carry a locale (#83): through the LocaleUrlRouting contract, which an
 * extension binds, rather than another extension's config.
 */
class LanguageFieldTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        // Pin a 32-byte AES-256-CBC key so the session encrypter does not
        // depend on the APP_KEY of the environment the suite runs in.
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

        // The admin views use core's view namespaces, which are registered
        // by providers this test does not boot (as in AdminGetRoutesSmokeTest).
        $this->app['view']->addNamespace('components', base_path('resources/views/components'));
        $this->app['view']->addNamespace('admin', base_path('resources/views/admin'));
        $this->app['view']->addNamespace('layouts', base_path('resources/views/layouts'));
        $this->app['view']->addNamespace('themes', __DIR__.'/../../stubs/theme');

        $this->admin = Member::factory()->create(['role' => MemberRole::ADMIN]);
    }

    protected function tearDown(): void
    {
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_the_language_field_is_hidden_when_nothing_binds_locale_urls(): void
    {
        $this->actingAs($this->admin, 'member')
            ->get(route('dixlase-pages::admin.pages.create'))
            ->assertOk()
            ->assertViewHas('multilingualEnabled', false)
            ->assertDontSee('name="lang"', false);
    }

    public function test_the_language_field_follows_the_bound_answer(): void
    {
        $this->bindLocaleUrls(true);

        $this->actingAs($this->admin, 'member')
            ->get(route('dixlase-pages::admin.pages.create'))
            ->assertOk()
            ->assertViewHas('multilingualEnabled', true)
            ->assertSee('name="lang"', false);

        $this->bindLocaleUrls(false);

        $this->actingAs($this->admin, 'member')
            ->get(route('dixlase-pages::admin.pages.create'))
            ->assertOk()
            ->assertViewHas('multilingualEnabled', false)
            ->assertDontSee('name="lang"', false);
    }

    private function bindLocaleUrls(bool $enabled): void
    {
        $this->app->instance(LocaleUrlRouting::class, new class($enabled) implements LocaleUrlRouting
        {
            public function __construct(private bool $enabled) {}

            public function isEnabled(): bool
            {
                return $this->enabled;
            }
        });
    }
}
