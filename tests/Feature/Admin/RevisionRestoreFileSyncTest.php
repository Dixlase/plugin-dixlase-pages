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
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Models\DixlasePagesPageRevision;
use Tests\TestCase;

/**
 * Restoring a revision of a page stored as a file must change the file, not
 * only the DB copy: the public page and the edit screen both read the file.
 */
class RevisionRestoreFileSyncTest extends TestCase
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

        $this->admin = Member::factory()->create(['role' => MemberRole::ADMIN]);
    }

    protected function tearDown(): void
    {
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_restoring_a_revision_rewrites_the_file(): void
    {
        $page = $this->createPage('about', 'First body');
        $first = $this->latestRevision($page);
        $this->updatePage($page, 'about', 'Second body');

        $this->restore($page, $first);

        $this->assertSame('First body', Storage::disk('local')->get('dixlase-pages/about.md'));
        $this->assertSame('First body', $page->refresh()->getAttributes()['content']);
    }

    public function test_restoring_a_revision_with_another_slug_moves_the_file(): void
    {
        $page = $this->createPage('about', 'First body');
        $first = $this->latestRevision($page);
        $this->updatePage($page, 'company', 'Second body');
        Storage::disk('local')->assertExists('dixlase-pages/company.md');

        $this->restore($page, $first);

        Storage::disk('local')->assertMissing('dixlase-pages/company.md');
        $this->assertSame('First body', Storage::disk('local')->get('dixlase-pages/about.md'));
        $this->assertSame('about', $page->refresh()->slug);
    }

    /**
     * @return array<string, mixed>
     */
    private function pageInput(string $slug, string $content): array
    {
        return [
            'title' => ucfirst($slug),
            'slug' => $slug,
            'content' => $content,
            'storage_type' => 'file',
            'editor_type' => 'markdown',
            'status' => 'draft',
            'lang' => 'en',
        ];
    }

    private function createPage(string $slug, string $content): DixlasePagesPage
    {
        $this->actingAs($this->admin, 'member')
            ->post(route('dixlase-pages::admin.pages.store'), $this->pageInput($slug, $content))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        return DixlasePagesPage::where('slug', $slug)->firstOrFail();
    }

    private function updatePage(DixlasePagesPage $page, string $slug, string $content): void
    {
        $this->actingAs($this->admin, 'member')
            ->patch(route('dixlase-pages::admin.pages.update', $page), $this->pageInput($slug, $content))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    private function latestRevision(DixlasePagesPage $page): DixlasePagesPageRevision
    {
        return DixlasePagesPageRevision::where('page_id', $page->id)->latest('id')->firstOrFail();
    }

    private function restore(DixlasePagesPage $page, DixlasePagesPageRevision $revision): void
    {
        $this->actingAs($this->admin, 'member')
            ->post(route('dixlase-pages::admin.pages.revisions.restore', [$page, $revision->id]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }
}
