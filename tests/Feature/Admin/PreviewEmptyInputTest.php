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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The previews read the form as it is posted (#94).
 *
 * Core is about to convert empty form fields to null (Laravel's
 * ConvertEmptyStringsToNull). Both previews must work whether an empty
 * field arrives as '' (today) or as null (afterwards), so these tests post
 * both.
 */
class PreviewEmptyInputTest extends TestCase
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

    /**
     * @return array<string, array{0: string|null}>
     */
    public static function emptyValues(): array
    {
        return [
            'empty string' => [''],
            'null' => [null],
        ];
    }

    #[DataProvider('emptyValues')]
    public function test_the_live_preview_renders_an_empty_body(?string $empty): void
    {
        $this->actingAs($this->admin, 'member')
            ->post(route('dixlase-pages::admin.pages.preview-render'), [
                'content' => $empty,
                'editor_type' => $empty,
            ])
            ->assertOk()
            ->assertJsonStructure(['html']);
    }

    #[DataProvider('emptyValues')]
    public function test_the_preview_renders_an_empty_form(?string $empty): void
    {
        $this->actingAs($this->admin, 'member')
            ->post(route('dixlase-pages::admin.pages.preview'), [
                'title' => $empty,
                'slug' => $empty,
                'content' => $empty,
                'custom_css' => $empty,
                'custom_js' => $empty,
                'editor_type' => $empty,
                'status' => $empty,
                'published_at' => $empty,
            ])
            ->assertOk();
    }
}
