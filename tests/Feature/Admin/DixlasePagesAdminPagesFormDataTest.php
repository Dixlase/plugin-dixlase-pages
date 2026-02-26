<?php

/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
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
        $this->controller = new class($contentService) extends DixlasePagesAdminPagesController
        {
            public function __construct(DixlasePagesPageContentService $contentService)
            {
                $this->contentService = $contentService;
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
        $this->assertArrayHasKey('editorTranslations', $result);
        $this->assertArrayHasKey('editorIcons', $result);
        $this->assertArrayHasKey('editorColors', $result);
        $this->assertArrayHasKey('statusValue', $result);
        $this->assertArrayHasKey('publishedAtValue', $result);
        $this->assertArrayHasKey('ogpImage', $result);
        $this->assertArrayHasKey('fileStorageBasePath', $result);

        $this->assertSame('', $result['content']);
        $this->assertSame('draft', $result['statusValue']);
        $this->assertSame('', $result['publishedAtValue']);
        $this->assertNull($result['ogpImage']);
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
            'storage_type' => 'database',
            'editor_type' => 'html',
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
            'storage_type' => 'database',
            'editor_type' => 'html',
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
            'storage_type' => 'file',
            'editor_type' => 'html',
            'content' => '<p>DB content</p>',
        ]);

        $fileContent = '<h1>File content</h1>';
        $result = $this->prepareFormData->invoke($this->controller, $page, $fileContent);

        $this->assertSame($fileContent, $result['content']);
    }

    /**
     * エディター翻訳キーが全て含まれることを確認
     */
    public function test_editor_translations_contain_all_required_keys(): void
    {
        $page = new DixlasePagesPage();
        $result = $this->prepareFormData->invoke($this->controller, $page);

        $requiredKeys = [
            'common.content_editor.gui',
            'common.content_editor.gui_description',
            'common.content_editor.markdown',
            'common.content_editor.markdown_description',
            'common.content_editor.html',
            'common.content_editor.html_description',
            'common.content_editor.blade',
            'common.content_editor.blade_description',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $result['editorTranslations']);
        }
    }

    /**
     * エディターアイコン・色マップが全エディタータイプを含むことを確認
     */
    public function test_editor_icons_and_colors_contain_all_editor_types(): void
    {
        $page = new DixlasePagesPage();
        $result = $this->prepareFormData->invoke($this->controller, $page);

        $requiredEditors = ['gui', 'markdown', 'html', 'blade'];

        foreach ($requiredEditors as $editor) {
            $this->assertArrayHasKey($editor, $result['editorIcons']);
            $this->assertArrayHasKey($editor, $result['editorColors']);
        }
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
            'storage_type' => 'database',
            'editor_type' => 'html',
            'status' => 'scheduled',
            'published_at' => $publishedAt,
        ]);

        $result = $this->prepareFormData->invoke($this->controller, $page);

        $this->assertSame(
            $publishedAt->format('Y-m-d\TH:i'),
            $result['publishedAtValue']
        );
    }
}
