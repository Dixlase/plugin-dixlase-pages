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

use App\Enums\ContentPublishMode;
use App\Enums\MemberRole;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\SiteSetting;
use App\Services\Content\FileContentRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Models\DixlasePagesPageRevision;
use Plugins\DixlasePages\App\Models\DixlasePagesPageSetting;
use Plugins\DixlasePages\App\Services\DixlasePagesPageContentService;
use Tests\TestCase;

/**
 * A page stored as a file is published through core's shared layer (#57).
 *
 * Under the default admin publish mode a change made to the files outside
 * the admin is recorded once and waits on the edit screen until someone
 * publishes it; under the file save mode it is taken in at once and recorded
 * as a revision.
 */
class FileContentPublishTest extends TestCase
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

        // Simple mode stores every page in the database; file storage needs
        // the advanced admin mode.
        SiteSetting::setValue('admin_mode', '1');

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

    public function test_the_page_model_is_registered_with_core(): void
    {
        $this->assertContains(DixlasePagesPage::class, app(FileContentRegistry::class)->models());
    }

    public function test_a_file_change_waits_to_be_published_under_the_default_mode(): void
    {
        $page = $this->createPage('about', 'Saved body');

        Storage::disk('local')->put('dixlase-pages/about.md', 'Edited on disk');

        $this->assertSame('Saved body', $page->refresh()->getContentByEditorType());
        // A second read of the same change does not record it again.
        $this->assertSame('Saved body', $page->refresh()->getContentByEditorType());

        $logs = $this->logs('dixlase_pages.page.file_changed');
        $this->assertCount(1, $logs);
        $this->assertSame(AuditLog::SEVERITY_WARNING, $logs->first()->severity);
        $this->assertSame(['content'], $logs->first()->context['parts'] ?? null);
        $this->assertFalse($logs->first()->context['published'] ?? null);
        $this->assertSame(1, app(FileContentRegistry::class)->pendingCount());
    }

    public function test_saving_from_the_admin_leaves_nothing_to_publish(): void
    {
        $page = $this->createPage('about', 'First body');
        $this->updatePage($page, 'Second body');

        $this->assertSame('Second body', $page->refresh()->getContentByEditorType());
        $this->assertCount(0, $this->logs('dixlase_pages.page.file_changed'));
        $this->assertSame(0, app(FileContentRegistry::class)->pendingCount());
    }

    public function test_the_edit_screen_shows_the_change_and_the_working_copy(): void
    {
        $page = $this->createPage('about', 'Saved body');
        Storage::disk('local')->put('dixlase-pages/about.md', 'Edited on disk');

        $this->actingAs($this->admin, 'member')
            ->get(route('dixlase-pages::admin.pages.edit', $page))
            ->assertOk()
            ->assertSee(__('common.file_changes.title'))
            ->assertSee(route('dixlase-pages::admin.pages.publish-file', $page), false)
            ->assertSee('Edited on disk');
    }

    public function test_publishing_takes_the_file_in_and_records_it(): void
    {
        $page = $this->createPage('about', 'Saved body');
        Storage::disk('local')->put('dixlase-pages/about.md', 'Edited on disk');
        $revisions = DixlasePagesPageRevision::where('page_id', $page->id)->count();

        $this->actingAs($this->admin, 'member')
            ->post(route('dixlase-pages::admin.pages.publish-file', $page))
            ->assertRedirect(route('dixlase-pages::admin.pages.edit', $page))
            ->assertSessionHas('success');

        $page->refresh();
        $this->assertSame('Edited on disk', $page->getAttributes()['content']);
        $this->assertSame('Edited on disk', $page->getContentByEditorType());
        $this->assertSame($revisions + 1, DixlasePagesPageRevision::where('page_id', $page->id)->count());

        $logs = $this->logs('dixlase_pages.page.published_from_file');
        $this->assertCount(1, $logs);
        $this->assertSame(['content'], $logs->first()->context['parts'] ?? null);
        $this->assertSame(0, app(FileContentRegistry::class)->pendingCount());
    }

    public function test_publishing_with_nothing_changed_fails(): void
    {
        $page = $this->createPage('about', 'Saved body');

        $this->actingAs($this->admin, 'member')
            ->post(route('dixlase-pages::admin.pages.publish-file', $page))
            ->assertRedirect()
            ->assertSessionHas('error', __('common.file_changes.nothing_to_publish'));

        $this->assertCount(0, $this->logs('dixlase_pages.page.published_from_file'));
    }

    public function test_a_member_below_the_publish_role_cannot_publish_a_live_page(): void
    {
        $page = $this->createPage('about', 'Saved body', 'published');
        Storage::disk('local')->put('dixlase-pages/about.md', 'Edited on disk');
        DixlasePagesPageSetting::setValue('publish_min_role', (string) MemberRole::ADMIN->value);
        $editor = Member::factory()->create(['role' => MemberRole::EDITOR]);

        $this->actingAs($editor, 'member')
            ->post(route('dixlase-pages::admin.pages.publish-file', $page))
            ->assertForbidden();

        $this->assertSame('Saved body', $page->refresh()->getAttributes()['content']);
    }

    public function test_the_file_save_mode_takes_a_change_in_at_once(): void
    {
        DixlasePagesPageSetting::setValue(DixlasePagesPage::PUBLISH_MODE_SETTING, ContentPublishMode::FILE_SAVE->value);
        $page = $this->createPage('about', 'Saved body');
        $revisions = DixlasePagesPageRevision::where('page_id', $page->id)->count();

        Storage::disk('local')->put('dixlase-pages/about.md', 'Edited on disk');

        $this->assertSame('Edited on disk', $page->refresh()->getContentByEditorType());
        $this->assertSame('Edited on disk', $page->refresh()->getAttributes()['content']);
        $this->assertSame($revisions + 1, DixlasePagesPageRevision::where('page_id', $page->id)->count());

        $logs = $this->logs('dixlase_pages.page.file_changed');
        $this->assertCount(1, $logs);
        $this->assertTrue($logs->first()->context['published'] ?? null);
        $this->assertSame(0, app(FileContentRegistry::class)->pendingCount());
    }

    public function test_the_custom_css_of_an_html_page_waits_as_well(): void
    {
        $page = $this->createPage('about', '<p>Body</p>', 'draft', 'html', ['custom_css' => 'p { color: red; }']);

        Storage::disk('local')->put('dixlase-pages/about.css', 'p { color: blue; }');

        $this->assertSame('p { color: red; }', app(DixlasePagesPageContentService::class)->getCssContent($page->refresh()));
        $this->assertSame(['custom_css'], $this->logs('dixlase_pages.page.file_changed')->first()?->context['parts']);
    }

    public function test_restoring_a_revision_replaces_a_waiting_change(): void
    {
        $page = $this->createPage('about', 'First body');
        $first = DixlasePagesPageRevision::where('page_id', $page->id)->latest('id')->firstOrFail();
        $this->updatePage($page, 'Second body');
        Storage::disk('local')->put('dixlase-pages/about.md', 'Edited on disk');
        $page->refresh()->getContentByEditorType();
        $this->assertSame(1, app(FileContentRegistry::class)->pendingCount());

        $this->actingAs($this->admin, 'member')
            ->post(route('dixlase-pages::admin.pages.revisions.restore', [$page, $first->id]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('First body', Storage::disk('local')->get('dixlase-pages/about.md'));
        $this->assertSame(0, app(FileContentRegistry::class)->pendingCount());
    }

    public function test_a_database_page_is_not_watched(): void
    {
        $page = $this->createPage('about', 'Saved body', 'draft', 'markdown', ['storage_type' => 'database']);

        $this->assertSame('Saved body', $page->refresh()->getContentByEditorType());
        $this->assertSame(['checked' => 0, 'pending' => 0], app(FileContentRegistry::class)->checkAll(app(\App\Services\Content\FileContentPublisher::class)));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createPage(string $slug, string $content, string $status = 'draft', string $editor = 'markdown', array $overrides = []): DixlasePagesPage
    {
        $this->actingAs($this->admin, 'member')
            ->post(route('dixlase-pages::admin.pages.store'), array_merge([
                'title' => ucfirst($slug),
                'slug' => $slug,
                'content' => $content,
                'storage_type' => 'file',
                'editor_type' => $editor,
                'status' => $status,
                'lang' => 'en',
            ], $overrides))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        return DixlasePagesPage::where('slug', $slug)->firstOrFail();
    }

    private function updatePage(DixlasePagesPage $page, string $content): void
    {
        $this->actingAs($this->admin, 'member')
            ->patch(route('dixlase-pages::admin.pages.update', $page), [
                'title' => ucfirst($page->slug),
                'slug' => $page->slug,
                'content' => $content,
                'storage_type' => 'file',
                'editor_type' => 'markdown',
                'status' => 'draft',
                'lang' => 'en',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    /**
     * @return \Illuminate\Support\Collection<int, AuditLog>
     */
    private function logs(string $action)
    {
        return AuditLog::where('action', $action)->get();
    }
}
