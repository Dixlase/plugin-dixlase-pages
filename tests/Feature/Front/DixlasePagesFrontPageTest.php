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

namespace Plugins\DixlasePages\Tests\Feature\Front;

use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Models\DixlasePagesPageSetting;
use Tests\TestCase;

/**
 * フロントページ表示とカスタムアセットのテスト
 */
class DixlasePagesFrontPageTest extends TestCase
{
    use RefreshDatabase;

    protected string $pagesDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        // プラグインのマイグレーションを実行
        $this->artisan('migrate', [
            '--path' => base_path('plugins/DixlasePages/database/migrations'),
            '--realpath' => true,
        ]);

        // プラグインの翻訳を登録
        $this->app['translator']->addNamespace(
            'dixlase-pages',
            base_path('plugins/DixlasePages/lang')
        );

        // プラグインのビュー名前空間を登録
        $this->app['view']->addNamespace(
            'dixlase-pages',
            base_path('plugins/DixlasePages/resources/views')
        );

        // テーマのビュー名前空間を登録（フロントビューがthemes::layouts.appを参照するため）
        if (! $this->app['view']->getFinder()->hasHintInformation('themes')) {
            $this->app['view']->addNamespace(
                'themes',
                base_path('themes/DixlaseOnePage/resources/views')
            );
        }

        // グローバルミドルウェアを無効化（テスト環境ではインストール/メンテナンスチェック不要）
        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMaintenanceMode::class,
        ]);

        // ルートスラッグを取得（RefreshDatabase後なのでデフォルト値 'page' が返る）
        $this->pagesDirectory = DixlasePagesPageSetting::getValue('route_slug', 'page');

        // RefreshDatabase後にフロントルートを再登録
        // （ブート時にはDB設定テーブルが未作成のためルートが未登録）
        $this->registerFrontRoutes();
    }

    /**
     * Register the plugin's real front routes, under the same `web` group
     * core loads them in, so the tests exercise routes/web.php itself rather
     * than a copy of it.
     */
    private function registerFrontRoutes(): void
    {
        Route::middleware('web')->group(base_path('plugins/DixlasePages/routes/web.php'));

        $routes = app('router')->getRoutes();
        $routes->refreshNameLookups();
        $routes->refreshActionLookups();
    }

    /**
     * 公開済みページが正常に表示されることを確認
     */
    public function test_published_page_is_accessible(): void
    {
        $page = DixlasePagesPage::factory()->published()->create([
            'slug' => 'test-page',
            'title' => 'Test Page',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'content' => '<p>Hello World</p>',
            'lang' => app()->getLocale(),
        ]);

        $response = $this->get('/'.$this->pagesDirectory.'/test-page');

        $response->assertStatus(200);
        $response->assertSee('Test Page');
        $response->assertSee('Hello World');
    }

    /**
     * Raw HTML in a Markdown page is shown as text, not rendered. Markdown is
     * open to editors below ADMIN, and HTML authoring is ADMIN-only.
     */
    public function test_markdown_page_escapes_raw_html(): void
    {
        DixlasePagesPage::factory()->published()->create([
            'slug' => 'markdown-html',
            'title' => 'Markdown HTML',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::MARKDOWN,
            'content' => "# Heading\n\n<img src=x onerror=alert(1)>\n\n<form action=\"https://evil.example\"><input></form>\n\n[link](javascript:alert(1))",
            'lang' => app()->getLocale(),
        ]);

        $response = $this->get('/'.$this->pagesDirectory.'/markdown-html');

        $response->assertStatus(200);
        $response->assertSee('<h1>Heading</h1>', false);
        $response->assertSee('&lt;img src=x onerror=alert(1)&gt;', false);
        $response->assertDontSee('<img src=x', false);
        $response->assertDontSee('<form action="https://evil.example"', false);
        $response->assertDontSee('href="javascript:', false);
    }

    /**
     * 下書きページが未ログインでは404になることを確認
     */
    public function test_draft_page_returns_404_for_guests(): void
    {
        DixlasePagesPage::factory()->draft()->create([
            'slug' => 'draft-page',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'lang' => app()->getLocale(),
        ]);

        $response = $this->get('/'.$this->pagesDirectory.'/draft-page');

        $response->assertStatus(404);
    }

    /**
     * A page authored in a locale other than the request locale and the
     * site primary still resolves (it is not filtered out by lang). This
     * supports the "write in one language first, translate later" workflow:
     * a Japanese-source page on an English-primary site stays reachable from
     * every locale URL, with the multilingual overlay supplying the right
     * translation at render time and the source content as the fallback.
     */
    public function test_page_authored_in_another_locale_is_accessible(): void
    {
        DixlasePagesPage::factory()->published()->create([
            'slug' => 'lang-test',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'lang' => 'fr',
        ]);

        $response = $this->get('/'.$this->pagesDirectory.'/lang-test');

        $response->assertStatus(200);
    }

    /**
     * 階層 URL (/page/parent/child) が公開ページを解決して 200 を返すことを確認
     */
    public function test_nested_path_resolves_child_page(): void
    {
        $parent = DixlasePagesPage::factory()->published()->create([
            'slug' => 'parent',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'lang' => app()->getLocale(),
        ]);
        DixlasePagesPage::factory()->published()->create([
            'slug' => 'child',
            'title' => 'Nested Page',
            'content' => '<p>Hello Nested</p>',
            'parent_id' => $parent->id,
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'lang' => app()->getLocale(),
        ]);

        $response = $this->get('/'.$this->pagesDirectory.'/parent/child');

        $response->assertStatus(200);
        $response->assertSee('Nested Page');
        $response->assertSee('Hello Nested');
    }

    /**
     * 親なしの子スラッグ単独 URL は 404 を返すことを確認
     */
    public function test_child_slug_without_parent_path_returns_404(): void
    {
        $parent = DixlasePagesPage::factory()->published()->create([
            'slug' => 'top',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'lang' => app()->getLocale(),
        ]);
        DixlasePagesPage::factory()->published()->create([
            'slug' => 'leaf',
            'parent_id' => $parent->id,
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'lang' => app()->getLocale(),
        ]);

        $response = $this->get('/'.$this->pagesDirectory.'/leaf');

        $response->assertStatus(404);
    }

    /**
     * カスタムCSSアセットが正しいContent-Typeで返されることを確認
     */
    public function test_custom_css_asset_returns_correct_content_type(): void
    {
        DixlasePagesPage::factory()->published()->create([
            'slug' => 'css-test',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'custom_css' => 'body { background: red; }',
            'lang' => app()->getLocale(),
        ]);

        $response = $this->get('/'.$this->pagesDirectory.'/css-test/custom-style.css');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/css; charset=UTF-8');
        $response->assertSee('body { background: red; }');
    }

    /**
     * カスタムJSアセットが正しいContent-Typeで返されることを確認
     */
    public function test_custom_js_asset_returns_correct_content_type(): void
    {
        DixlasePagesPage::factory()->published()->create([
            'slug' => 'js-test',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'custom_js' => 'console.log("hello");',
            'lang' => app()->getLocale(),
        ]);

        $response = $this->get('/'.$this->pagesDirectory.'/js-test/custom-script.js');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/javascript; charset=UTF-8');
        $response->assertSee('console.log("hello");', false);
    }

    /**
     * A Markdown page serves its own CSS, never JavaScript.
     */
    public function test_a_markdown_page_serves_its_css_but_never_a_script(): void
    {
        DixlasePagesPage::factory()->published()->create([
            'slug' => 'md-styled',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::MARKDOWN,
            'content' => 'Note {.note}',
            'custom_css' => '.note { color: red; }',
            // Stored before Markdown kept CSS only; never served.
            'custom_js' => 'console.log(1);',
            'lang' => app()->getLocale(),
        ]);

        $this->get('/'.$this->pagesDirectory.'/md-styled')
            ->assertOk()
            ->assertSee('md-styled/custom-style.css', false)
            ->assertDontSee('custom-script.js', false);
        $this->get('/'.$this->pagesDirectory.'/md-styled/custom-style.css')
            ->assertOk()
            ->assertSee('.note { color: red; }');
        $this->get('/'.$this->pagesDirectory.'/md-styled/custom-script.js')->assertNotFound();
    }

    /**
     * カスタムCSS/JSが空の場合は404を返すことを確認
     */
    public function test_empty_custom_css_returns_404(): void
    {
        DixlasePagesPage::factory()->published()->create([
            'slug' => 'no-css',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'custom_css' => null,
            'lang' => app()->getLocale(),
        ]);

        $response = $this->get('/'.$this->pagesDirectory.'/no-css/custom-style.css');

        $response->assertStatus(404);
    }

    /**
     * カスタムアセットにCache-ControlとETagヘッダーが含まれることを確認
     */
    public function test_custom_asset_has_cache_headers(): void
    {
        DixlasePagesPage::factory()->published()->create([
            'slug' => 'cache-test',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'custom_css' => '.test { color: blue; }',
            'lang' => app()->getLocale(),
        ]);

        $response = $this->get('/'.$this->pagesDirectory.'/cache-test/custom-style.css');

        $response->assertStatus(200);
        $response->assertHeader('Cache-Control');
        $response->assertHeader('ETag');
    }

    /**
     * ビューにeditorTypeとcontent変数が渡されていることを確認
     */
    public function test_front_page_passes_editor_type_and_content_to_view(): void
    {
        DixlasePagesPage::factory()->published()->create([
            'slug' => 'view-test',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'content' => '<h1>View Test</h1>',
            'lang' => app()->getLocale(),
        ]);

        $response = $this->get('/'.$this->pagesDirectory.'/view-test');

        $response->assertStatus(200);
        $response->assertViewHas('editorType', 'html');
        $response->assertViewHas('content', '<h1>View Test</h1>');
        $response->assertViewHas('hasCustomCss');
        $response->assertViewHas('hasCustomJs');
        $response->assertViewHas('customAssetVersion');
    }

    /**
     * A Markdown page must render javascript: links exactly as the preview
     * does -- stripped -- not as live hrefs.
     */
    public function test_markdown_page_strips_javascript_links(): void
    {
        DixlasePagesPage::factory()->published()->create([
            'slug' => 'md-links',
            'title' => 'Links',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::MARKDOWN,
            'content' => "[click](javascript:alert(1))\n\n[ok](https://example.com)",
            'lang' => app()->getLocale(),
        ]);

        $response = $this->get('/'.$this->pagesDirectory.'/md-links');

        $response->assertStatus(200);
        $response->assertDontSee('javascript:alert', false);
        $response->assertSee('href="https://example.com"', false);
    }

    /**
     * A draft's custom assets are served only to logged-in members, so a
     * shared cache must never store them; a published page's may be cached.
     */
    public function test_draft_assets_are_not_publicly_cacheable(): void
    {
        DixlasePagesPage::factory()->draft()->create([
            'slug' => 'draft-assets',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'custom_js' => 'console.log(1);',
            'lang' => app()->getLocale(),
        ]);
        DixlasePagesPage::factory()->published()->create([
            'slug' => 'live-assets',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'custom_js' => 'console.log(2);',
            'lang' => app()->getLocale(),
        ]);

        $this->actingAs(\App\Models\Member::factory()->create(), 'member');

        $draft = $this->get('/'.$this->pagesDirectory.'/draft-assets/custom-script.js');
        $draft->assertStatus(200);
        $this->assertStringContainsString('no-store', (string) $draft->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('public', (string) $draft->headers->get('Cache-Control'));

        $live = $this->get('/'.$this->pagesDirectory.'/live-assets/custom-script.js');
        $live->assertStatus(200);
        $this->assertStringContainsString('public', (string) $live->headers->get('Cache-Control'));
    }

    /**
     * A member signed in to the admin is served a draft, with the preview
     * banner, and the response is kept out of shared caches.
     *
     * The admin login lives in the member guard's own session row, which a
     * front request does not open, so the guard sees nobody here; the row
     * is written by hand under the request's session id, as on a live site.
     */
    public function test_a_member_signed_in_to_the_admin_sees_a_draft(): void
    {
        DixlasePagesPage::factory()->draft()->create([
            'slug' => 'draft-preview',
            'title' => 'Draft Preview',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'content' => '<p>Not yet public</p>',
            'lang' => app()->getLocale(),
        ]);

        $response = $this->asMemberSignedInToTheAdmin()
            ->get('/'.$this->pagesDirectory.'/draft-preview');

        $response->assertStatus(200);
        $response->assertSee('Not yet public');
        $response->assertSee(__('dixlase-pages::front/page.preview_mode'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('public', (string) $response->headers->get('Cache-Control'));
    }

    /**
     * The same member also sees a page scheduled for later, and its custom
     * assets, so the preview is styled.
     */
    public function test_a_member_signed_in_to_the_admin_sees_a_scheduled_page_and_its_assets(): void
    {
        DixlasePagesPage::factory()->scheduled()->create([
            'slug' => 'scheduled-preview',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'content' => '<p>Coming soon</p>',
            'custom_css' => '.soon { color: green; }',
            'published_at' => now()->addWeek(),
            'lang' => app()->getLocale(),
        ]);

        $this->asMemberSignedInToTheAdmin()
            ->get('/'.$this->pagesDirectory.'/scheduled-preview')
            ->assertStatus(200)
            ->assertSee('Coming soon');

        $this->asMemberSignedInToTheAdmin()
            ->get('/'.$this->pagesDirectory.'/scheduled-preview/custom-style.css')
            ->assertStatus(200)
            ->assertSee('.soon { color: green; }');
    }

    /**
     * A visitor still gets 404 for a page scheduled for later and for its
     * assets.
     */
    public function test_a_scheduled_page_returns_404_for_guests(): void
    {
        DixlasePagesPage::factory()->scheduled()->create([
            'slug' => 'scheduled-hidden',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'custom_css' => '.soon { color: green; }',
            'published_at' => now()->addWeek(),
            'lang' => app()->getLocale(),
        ]);

        $this->get('/'.$this->pagesDirectory.'/scheduled-hidden')->assertStatus(404);
        $this->get('/'.$this->pagesDirectory.'/scheduled-hidden/custom-style.css')->assertStatus(404);
    }

    /**
     * A published page carries no preview banner, even for a member.
     */
    public function test_a_published_page_shows_no_preview_banner(): void
    {
        DixlasePagesPage::factory()->published()->create([
            'slug' => 'live-page',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'lang' => app()->getLocale(),
        ]);

        $this->asMemberSignedInToTheAdmin()
            ->get('/'.$this->pagesDirectory.'/live-page')
            ->assertStatus(200)
            ->assertDontSee(__('dixlase-pages::front/page.preview_mode'));
    }

    /**
     * Send the request with a session cookie whose id has a signed-in row
     * in the member guard's session table, and nobody on the guard.
     */
    private function asMemberSignedInToTheAdmin(): static
    {
        $sessionId = str_repeat('a', 40);
        $member = Member::factory()->create();

        DB::table('members_sessions')->updateOrInsert(['id' => $sessionId], [
            'member_id' => $member->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => base64_encode(serialize([])),
            'last_activity' => time(),
        ]);

        return $this->withCookie((string) config('session.cookie'), $sessionId);
    }
}
