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
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Tests\TestCase;

/**
 * Every page lifecycle write from the admin leaves an audit entry naming the
 * actor and the page: create, update, move to trash, restore from trash, and
 * permanent deletion (one entry per page when the trash is emptied).
 *
 * The editor case guards the permission model: who may author pages is
 * decided per route by config/admin/roles.php, and the audited actions must
 * not add an ADMIN-only core permission on top of it.
 */
class PageLifecycleAuditTest extends TestCase
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

        // File-stored pages must never write into the real storage tree.
        Storage::fake('local');

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

        $this->admin = Member::factory()->create(['role' => MemberRole::ADMIN]);
    }

    protected function tearDown(): void
    {
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_creating_a_page_is_audited(): void
    {
        $this->actingAs($this->admin, 'member')
            ->post(route('dixlase-pages::admin.pages.store'), $this->pageInput('about'))
            ->assertRedirect();

        $page = DixlasePagesPage::where('slug', 'about')->firstOrFail();
        $log = $this->assertAudited('dixlase_pages.page.created', $page, $this->admin);

        $this->assertSame('about', $log->context['slug'] ?? null);
        $this->assertSame('database', $log->context['storage_type'] ?? null);
    }

    public function test_an_editor_can_still_create_a_page_and_it_is_audited(): void
    {
        $editor = Member::factory()->create(['role' => MemberRole::EDITOR]);

        $this->actingAs($editor, 'member')
            ->post(route('dixlase-pages::admin.pages.store'), $this->pageInput('editor-page'))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $page = DixlasePagesPage::where('slug', 'editor-page')->firstOrFail();
        $this->assertAudited('dixlase_pages.page.created', $page, $editor);
    }

    public function test_updating_a_page_is_audited_with_the_changed_fields(): void
    {
        $page = $this->createPage('about');

        $this->actingAs($this->admin, 'member')
            ->patch(route('dixlase-pages::admin.pages.update', $page), $this->pageInput('company', 'New body'))
            ->assertRedirect();

        $log = $this->assertAudited('dixlase_pages.page.updated', $page->refresh(), $this->admin);

        $this->assertContains('slug', $log->context['changed'] ?? []);
        $this->assertContains('content', $log->context['changed'] ?? []);
        $this->assertSame('about', $log->context['previous_slug'] ?? null);
    }

    public function test_updating_a_file_stored_page_writes_the_file_and_is_audited(): void
    {
        // Simple mode stores every page in the database; file storage needs
        // the advanced admin mode.
        SiteSetting::setValue('admin_mode', '1');

        $page = $this->createPage('about', 'file');

        $this->actingAs($this->admin, 'member')
            ->patch(route('dixlase-pages::admin.pages.update', $page), $this->pageInput('about', 'From the editor', 'file'))
            ->assertRedirect();

        Storage::disk('local')->assertExists('dixlase-pages/about.md');
        $this->assertSame('From the editor', Storage::disk('local')->get('dixlase-pages/about.md'));
        $this->assertAudited('dixlase_pages.page.updated', $page, $this->admin);
    }

    public function test_moving_a_page_to_the_trash_and_back_is_audited(): void
    {
        $page = $this->createPage('about');

        $this->actingAs($this->admin, 'member')
            ->delete(route('dixlase-pages::admin.pages.destroy', $page))
            ->assertRedirect();

        $this->assertSoftDeleted($page);
        $this->assertAudited('dixlase_pages.page.deleted', $page, $this->admin);

        $this->actingAs($this->admin, 'member')
            ->post(route('dixlase-pages::admin.pages.trash.restore', $page->id))
            ->assertRedirect();

        $this->assertNotSoftDeleted($page);
        $this->assertAudited('dixlase_pages.page.restored', $page, $this->admin);
    }

    public function test_deleting_a_page_permanently_is_audited(): void
    {
        $page = $this->createPage('about');
        $page->delete();

        $this->actingAs($this->admin, 'member')
            ->delete(route('dixlase-pages::admin.pages.trash.force-destroy', $page->id))
            ->assertRedirect();

        $this->assertModelMissing($page);
        $this->assertAudited('dixlase_pages.page.force_deleted', $page, $this->admin);
    }

    public function test_emptying_the_trash_audits_each_page(): void
    {
        $first = $this->createPage('first');
        $second = $this->createPage('second');
        $first->delete();
        $second->delete();

        $this->actingAs($this->admin, 'member')
            ->post(route('dixlase-pages::admin.pages.trash.empty'))
            ->assertRedirect();

        $this->assertAudited('dixlase_pages.page.force_deleted', $first, $this->admin);
        $this->assertAudited('dixlase_pages.page.force_deleted', $second, $this->admin);
    }

    /**
     * @return array<string, mixed>
     */
    private function pageInput(string $slug, string $content = 'Body', string $storage = 'database'): array
    {
        return [
            'title' => ucfirst($slug),
            'slug' => $slug,
            'content' => $content,
            'storage_type' => $storage,
            'editor_type' => 'markdown',
            'status' => 'draft',
            'lang' => 'en',
        ];
    }

    private function createPage(string $slug, string $storage = 'database'): DixlasePagesPage
    {
        $this->actingAs($this->admin, 'member')
            ->post(route('dixlase-pages::admin.pages.store'), $this->pageInput($slug, 'Body', $storage))
            ->assertRedirect();

        return DixlasePagesPage::where('slug', $slug)->firstOrFail();
    }

    /**
     * Find the entry by action and the page's slug, which the action records
     * as the target label, and check the actor by name.
     *
     * Core's AuditLog::log() reads the actor and target only from model
     * instances, so the ids an action passes are not stored yet; matching on
     * them would fail for reasons outside this plugin.
     */
    private function assertAudited(string $action, DixlasePagesPage $page, Member $actor): AuditLog
    {
        $log = AuditLog::query()
            ->where('action', $action)
            ->where('target_label', $page->slug)
            ->latest('id')
            ->first();

        $this->assertNotNull($log, "No [{$action}] audit entry for page [{$page->slug}].");
        $this->assertSame((new MemberActor($actor))->getActorName(), $log->actor_name);

        return $log;
    }
}
