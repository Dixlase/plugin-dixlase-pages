<?php

/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Plugins\DixlasePages\App\Http\Controllers\Front\DixlasePagesCustomAssetController;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Models\DixlasePagesPageSetting;
use Plugins\DixlasePages\App\Services\DixlasePagesPageContentService;
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

        // ルートスラッグを取得（RefreshDatabase後なのでデフォルト値 'pages' が返る）
        $this->pagesDirectory = DixlasePagesPageSetting::getValue('route_slug', 'pages');

        // RefreshDatabase後にフロントルートを再登録
        // （ブート時にはDB設定テーブルが未作成のためルートが未登録）
        $this->registerFrontRoutes();
    }

    /**
     * フロントルートをテスト用に登録
     */
    private function registerFrontRoutes(): void
    {
        $dir = $this->pagesDirectory;

        Route::middleware(['front.ip'])
            ->group(function () use ($dir) {
                Route::get($dir.'/{slug}/custom-style.css', [DixlasePagesCustomAssetController::class, 'style'])
                    ->name('dixlase-pages::page.custom-style');

                Route::get($dir.'/{slug}/custom-script.js', [DixlasePagesCustomAssetController::class, 'script'])
                    ->name('dixlase-pages::page.custom-script');

                Route::get($dir.'/{slug}', function ($slug) {
                    $locale = app()->getLocale();

                    if (auth('member')->check()) {
                        $page = DixlasePagesPage::where('slug', $slug)
                            ->forLang($locale)
                            ->firstOrFail();
                    } else {
                        $page = DixlasePagesPage::where('slug', $slug)
                            ->forLang($locale)
                            ->published()
                            ->firstOrFail();
                    }

                    $contentService = app(DixlasePagesPageContentService::class);
                    $editorType = $page->editor_type->slug() ?? 'html';
                    $content = $page->getContentByEditorType() ?? '';
                    $hasCustomCss = ! empty($contentService->getCssContent($page, $locale));
                    $hasCustomJs = ! empty($contentService->getJsContent($page, $locale));
                    $customAssetVersion = $page->updated_at?->timestamp ?? time();

                    return view('dixlase-pages::front.page', compact(
                        'page',
                        'editorType',
                        'content',
                        'hasCustomCss',
                        'hasCustomJs',
                        'customAssetVersion',
                    ));
                })->name('dixlase-pages::page.show');
            });
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
     * 異なる言語のページが404になることを確認（forLangスコープ）
     */
    public function test_page_with_different_lang_returns_404(): void
    {
        DixlasePagesPage::factory()->published()->create([
            'slug' => 'lang-test',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'lang' => 'fr',
        ]);

        $response = $this->get('/'.$this->pagesDirectory.'/lang-test');

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
}
