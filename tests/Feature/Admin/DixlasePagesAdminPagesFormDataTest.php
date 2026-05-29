<?php

/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc.
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

use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Plugins\DixlasePages\App\Http\Controllers\Admin\DixlasePagesAdminPagesController;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Services\DixlasePagesPageContentService;
use ReflectionMethod;
use Tests\TestCase;

/**
 * prepareFormData()が正しいフォームデータを返すかテスト
 */
class DixlasePagesAdminPagesFormDataTest extends TestCase
{
    use RefreshDatabase;

    protected object $controller;

    protected ReflectionMethod $prepareFormData;

    protected function setUp(): void
    {
        parent::setUp();

        // プラグインのマイグレーションを実行
        $this->artisan('migrate', [
            '--path' => base_path('plugins/DixlasePages/database/migrations'),
            '--realpath' => true,
        ]);

        // プラグインの翻訳を登録（ServiceProvider登録を回避）
        $this->app['translator']->addNamespace(
            'dixlase-pages',
            base_path('plugins/DixlasePages/lang')
        );

        // コントローラーを生成（AdminInterfaceTrait初期化を回避）
        $contentService = $this->app->make(DixlasePagesPageContentService::class);
        $member = \App\Models\Member::factory()->create([
            'role' => \App\Enums\MemberRole::ADMIN,
        ]);
        $mediaRepository = $this->app->make(\App\Contracts\Repositories\MediaRepositoryInterface::class);
        $this->controller = new class($contentService, $member, $mediaRepository) extends DixlasePagesAdminPagesController
        {
            public function __construct(
                DixlasePagesPageContentService $contentService,
                $member,
                \App\Contracts\Repositories\MediaRepositoryInterface $mediaRepository
            ) {
                $this->contentService = $contentService;
                $this->member = $member;
                $this->mediaRepository = $mediaRepository;
            }
        };

        // prepareFormData()をリフレクションでアクセス可能にする
        $this->prepareFormData = new ReflectionMethod(
            DixlasePagesAdminPagesController::class,
            'prepareFormData'
        );
        $this->prepareFormData->setAccessible(true);
    }

    /**
     * 新規ページで正しいデフォルト値が返されることを確認
     */
    public function test_prepare_form_data_returns_correct_defaults_for_new_page(): void
    {
        $page = new DixlasePagesPage();
        $result = $this->prepareFormData->invoke($this->controller, $page);

        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('slugBaseUrl', $result);
        $this->assertArrayHasKey('storageOptions', $result);
        $this->assertArrayHasKey('statusOptions', $result);
        $this->assertArrayHasKey('editorCardOptions', $result);
        $this->assertArrayHasKey('statusValue', $result);
        $this->assertArrayHasKey('publishedAtValue', $result);
        $this->assertArrayHasKey('fileStorageBasePath', $result);
        $this->assertArrayHasKey('previewUrl', $result);

        $this->assertSame('', $result['content']);
        $this->assertSame('draft', $result['statusValue']);
        $this->assertSame('', $result['publishedAtValue']);
    }

    /**
     * ストレージオプションが正しい構造を持つことを確認
     */
    public function test_storage_options_have_correct_structure(): void
    {
        $page = new DixlasePagesPage();
        $result = $this->prepareFormData->invoke($this->controller, $page);

        $storageOptions = $result['storageOptions'];
        $this->assertCount(2, $storageOptions);
        $this->assertArrayHasKey('database', $storageOptions);
        $this->assertArrayHasKey('file', $storageOptions);
        $this->assertIsString($storageOptions['database']);
        $this->assertIsString($storageOptions['file']);

        $storageDescriptions = $result['storageDescriptions'];
        $this->assertCount(2, $storageDescriptions);
        $this->assertArrayHasKey('database', $storageDescriptions);
        $this->assertArrayHasKey('file', $storageDescriptions);
    }

    /**
     * ステータスオプションが正しい構造を持つことを確認
     */
    public function test_status_options_have_correct_structure(): void
    {
        $page = new DixlasePagesPage();
        $result = $this->prepareFormData->invoke($this->controller, $page);

        $statusOptions = $result['statusOptions'];
        $this->assertCount(3, $statusOptions);
        $this->assertArrayHasKey('draft', $statusOptions);
        $this->assertArrayHasKey('published', $statusOptions);
        $this->assertArrayHasKey('scheduled', $statusOptions);
    }

    /**
     * 既存ページのステータス値が正しく取得されることを確認
     */
    public function test_prepare_form_data_returns_correct_status_for_existing_page(): void
    {
        $page = DixlasePagesPage::factory()->published()->create([
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'content' => '<p>テスト</p>',
        ]);

        $result = $this->prepareFormData->invoke($this->controller, $page);

        $this->assertSame('published', $result['statusValue']);
    }

    /**
     * 既存ページのコンテンツがDB保存時に正しく取得されることを確認
     */
    public function test_prepare_form_data_returns_correct_content_from_database(): void
    {
        $page = DixlasePagesPage::factory()->create([
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'content' => '<h1>DBコンテンツ</h1>',
        ]);

        $result = $this->prepareFormData->invoke($this->controller, $page);

        $this->assertSame('<h1>DBコンテンツ</h1>', $result['content']);
    }

    /**
     * fileContents引数が渡された場合はそちらが優先されることを確認
     */
    public function test_prepare_form_data_prefers_file_contents_over_model(): void
    {
        $page = DixlasePagesPage::factory()->create([
            'storage_type' => ContentStorageType::FILE,
            'editor_type' => ContentEditorType::HTML,
            'content' => '<p>DB content</p>',
        ]);

        $fileContent = '<h1>File content</h1>';
        $result = $this->prepareFormData->invoke($this->controller, $page, $fileContent);

        $this->assertSame($fileContent, $result['content']);
    }

    /**
     * エディターカードオプションが全エディタータイプを含むことを確認（詳細モード）
     */
    public function test_editor_card_options_contain_all_required_types(): void
    {
        // Set to advanced mode so all editors are available
        SiteSetting::setValue('admin_mode', 1);

        $page = new DixlasePagesPage();
        $result = $this->prepareFormData->invoke($this->controller, $page);

        $values = array_column($result['editorCardOptions'], 'value');
        $this->assertContains('gui', $values);
        $this->assertContains('markdown', $values);
        $this->assertContains('html', $values);

        // 各オプションに必要なキーがあることを確認
        foreach ($result['editorCardOptions'] as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
            $this->assertArrayHasKey('icon', $option);
            $this->assertArrayHasKey('description', $option);
        }
    }

    /**
     * 簡単モードではGUI/Markdownのみ含まれることを確認
     */
    public function test_editor_card_options_in_simple_mode_contain_only_gui_and_markdown(): void
    {
        // Set to simple mode
        SiteSetting::setValue('admin_mode', 0);

        $page = new DixlasePagesPage();
        $result = $this->prepareFormData->invoke($this->controller, $page);

        $values = array_column($result['editorCardOptions'], 'value');
        $this->assertContains('gui', $values);
        $this->assertContains('markdown', $values);
        $this->assertNotContains('html', $values);
    }

    /**
     * エディターカードオプションにアイコンと説明が含まれることを確認（詳細モード）
     */
    public function test_editor_card_options_have_icons_and_descriptions(): void
    {
        // Set to advanced mode so all editors are available
        SiteSetting::setValue('admin_mode', 1);

        $page = new DixlasePagesPage();
        $result = $this->prepareFormData->invoke($this->controller, $page);

        foreach ($result['editorCardOptions'] as $option) {
            $this->assertNotEmpty($option['icon'], "Icon should not be empty for {$option['value']}");
            $this->assertNotEmpty($option['label'], "Label should not be empty for {$option['value']}");
        }
    }

    /**
     * 簡単モードでHTML/Bladeページ編集時はそのエディタタイプが含まれることを確認
     */
    public function test_simple_mode_allows_editing_existing_html_page(): void
    {
        // Set to simple mode
        SiteSetting::setValue('admin_mode', 0);

        $page = DixlasePagesPage::factory()->create([
            'editor_type' => ContentEditorType::HTML,
            'storage_type' => ContentStorageType::DATABASE,
        ]);
        $result = $this->prepareFormData->invoke($this->controller, $page);

        // HTML editor should be available for existing HTML pages even in simple mode
        $values = array_column($result['editorCardOptions'], 'value');
        $this->assertContains('html', $values);
        $this->assertTrue($result['isAdvancedEditor']);
    }

    /**
     * customCss/customJsが正しく返されることを確認
     */
    public function test_prepare_form_data_returns_custom_css_and_js(): void
    {
        $page = new DixlasePagesPage();
        $result = $this->prepareFormData->invoke($this->controller, $page);

        $this->assertArrayHasKey('customCss', $result);
        $this->assertArrayHasKey('customJs', $result);
        $this->assertSame('', $result['customCss']);
        $this->assertSame('', $result['customJs']);
    }

    /**
     * customCss/customJs引数が渡された場合はそちらが優先されることを確認
     */
    public function test_prepare_form_data_prefers_passed_custom_css_js_over_model(): void
    {
        $page = DixlasePagesPage::factory()->create([
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'custom_css' => 'body { color: red; }',
            'custom_js' => 'console.log("db");',
        ]);

        $result = $this->prepareFormData->invoke(
            $this->controller, $page, null, '.file-css { }', 'alert("file");'
        );

        $this->assertSame('.file-css { }', $result['customCss']);
        $this->assertSame('alert("file");', $result['customJs']);
    }

    /**
     * 既存ページのcustomCss/customJsがDBから取得されることを確認
     */
    public function test_prepare_form_data_returns_custom_css_js_from_model(): void
    {
        $page = DixlasePagesPage::factory()->create([
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'custom_css' => 'h1 { font-size: 2em; }',
            'custom_js' => 'document.title = "test";',
        ]);

        $result = $this->prepareFormData->invoke($this->controller, $page);

        $this->assertSame('h1 { font-size: 2em; }', $result['customCss']);
        $this->assertSame('document.title = "test";', $result['customJs']);
    }

    /**
     * 言語オプションが返されることを確認
     */
    public function test_prepare_form_data_returns_language_options(): void
    {
        $page = new DixlasePagesPage();
        $result = $this->prepareFormData->invoke($this->controller, $page);

        $this->assertArrayHasKey('languageOptions', $result);
        $this->assertArrayHasKey('langValue', $result);
        $this->assertIsArray($result['languageOptions']);
    }

    /**
     * 編集モードでも langValue は既存ページの値で言語オプションが露出することを確認。
     * (UI 上で言語ピッカーを再表示する変更と対応)
     */
    public function test_prepare_form_data_returns_existing_lang_on_edit(): void
    {
        $page = DixlasePagesPage::factory()->create([
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'lang' => 'en',
        ]);

        $result = $this->prepareFormData->invoke($this->controller, $page);

        $this->assertSame('en', $result['langValue']);
        $this->assertIsArray($result['languageOptions']);
    }

    /**
     * slugBaseUrlにアプリURLが含まれスラッシュで終わることを確認
     */
    public function test_slug_base_url_contains_app_url_and_ends_with_slash(): void
    {
        $page = new DixlasePagesPage();
        $result = $this->prepareFormData->invoke($this->controller, $page);

        $this->assertStringStartsWith(config('app.url'), $result['slugBaseUrl']);
        $this->assertStringEndsWith('/', $result['slugBaseUrl']);
    }

    /**
     * 公開日時が正しい形式で返されることを確認
     */
    public function test_published_at_value_formatted_correctly(): void
    {
        $publishedAt = now()->startOfMinute();
        $page = DixlasePagesPage::factory()->create([
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'status' => ContentStatus::SCHEDULED,
            'published_at' => $publishedAt,
        ]);

        $result = $this->prepareFormData->invoke($this->controller, $page);

        $this->assertSame(
            $publishedAt->format('Y-m-d\TH:i'),
            $result['publishedAtValue']
        );
    }
}
