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

use App\Actors\MemberActor;
use App\Enums\MemberRole;
use App\Models\AuditLog;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Plugins\DixlasePages\App\Actions\Settings\UpdateDixlasePagesSettingsAction;
use Plugins\DixlasePages\App\Models\DixlasePagesPageSetting;
use Tests\TestCase;

/**
 * Saving the plugin's settings leaves one audit entry naming the actor, the
 * changed keys and their old and new values, and none when nothing changed.
 */
class SettingsAuditTest extends TestCase
{
    use RefreshDatabase;

    private Member $superAdmin;

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

        // Editing the settings is SUPER_ADMIN only (config/admin/roles.php).
        $this->superAdmin = Member::factory()->create(['role' => MemberRole::SUPER_ADMIN]);

        DixlasePagesPageSetting::setMany($this->settingsInput());
    }

    protected function tearDown(): void
    {
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_changing_a_setting_is_audited_with_its_old_and_new_value(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->post(route('dixlase-pages::admin.pages.settings.update'), $this->settingsInput([
                'default_status' => 'published',
                'publish_min_role' => MemberRole::ADMIN->value,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('published', DixlasePagesPageSetting::getValue('default_status'));

        $logs = $this->settingsLogs();
        $this->assertCount(1, $logs);

        $log = $logs->first();
        $this->assertSame((new MemberActor($this->superAdmin))->getActorName(), $log->actor_name);
        $this->assertEqualsCanonicalizing(['default_status', 'publish_min_role'], $log->context['changed_keys'] ?? []);
        // assertEquals, not assertSame: MySQL's JSON type reorders object
        // keys, so the entry reads back as to/from rather than from/to.
        $this->assertEquals(
            ['from' => 'draft', 'to' => 'published'],
            $log->context['diff']['default_status'] ?? null,
        );
        $this->assertEquals(
            ['from' => (string) MemberRole::EDITOR->value, 'to' => (string) MemberRole::ADMIN->value],
            $log->context['diff']['publish_min_role'] ?? null,
        );
    }

    public function test_saving_without_a_change_is_not_audited(): void
    {
        $this->actingAs($this->superAdmin, 'member')
            ->post(route('dixlase-pages::admin.pages.settings.update'), $this->settingsInput())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertCount(0, $this->settingsLogs());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function settingsInput(array $overrides = []): array
    {
        return array_merge([
            'route_slug' => 'page',
            'default_status' => 'draft',
            'default_editor_type' => 'html',
            'default_storage_type' => 'database',
            'publish_min_role' => MemberRole::EDITOR->value,
        ], $overrides);
    }

    /**
     * @return \Illuminate\Support\Collection<int, AuditLog>
     */
    private function settingsLogs()
    {
        return AuditLog::query()
            ->where('target_label', UpdateDixlasePagesSettingsAction::SETTINGS_PAGE)
            ->get();
    }
}
