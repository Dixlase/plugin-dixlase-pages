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

use App\Contracts\PluginIntegration\SeoMetaProviderInterface;
use App\DTO\PluginIntegration\SeoMetaDTO;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Plugins\DixlasePages\App\Http\Controllers\Admin\DixlasePagesAdminPagesController;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Services\DixlasePagesPageContentService;
use ReflectionMethod;
use Tests\TestCase;

/**
 * SEOプラグイン連携のテスト
 *
 * - Contract経由のoptional依存として動作確認
 * - isEnabledForPlugin() が false の場合の安全装置
 * - ページ強制削除時のカスケード削除
 */
class SeoMetaIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected object $controller;

    protected ReflectionMethod $prepareFormData;

    protected ReflectionMethod $saveSeoMeta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--path' => base_path('plugins/DixlasePages/database/migrations'),
            '--realpath' => true,
        ]);

        $this->app['translator']->addNamespace(
            'dixlase-pages',
            base_path('plugins/DixlasePages/lang')
        );

        $contentService = $this->app->make(DixlasePagesPageContentService::class);
        $member = \App\Models\Member::factory()->create([
            'role' => \App\Enums\MemberRole::ADMIN,
        ]);
        $this->controller = new class($contentService, $member) extends DixlasePagesAdminPagesController
        {
            public function __construct(DixlasePagesPageContentService $contentService, $member)
            {
                $this->contentService = $contentService;
                $this->member = $member;
            }
        };

        $this->prepareFormData = new ReflectionMethod(
            DixlasePagesAdminPagesController::class,
            'prepareFormData'
        );
        $this->prepareFormData->setAccessible(true);

        $this->saveSeoMeta = new ReflectionMethod(
            DixlasePagesAdminPagesController::class,
            'saveSeoMeta'
        );
        $this->saveSeoMeta->setAccessible(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * SEOプラグイン未バインド時: seoMetaEnabled は false
     */
    public function test_seo_meta_disabled_when_provider_not_bound(): void
    {
        // SeoMetaProviderInterface をバインドしない
        $this->assertFalse($this->app->has(SeoMetaProviderInterface::class));

        $page = new DixlasePagesPage();
        $result = $this->prepareFormData->invoke($this->controller, $page);

        $this->assertFalse($result['seoMetaEnabled']);
        $this->assertNull($result['seoMeta']);
        $this->assertNull($result['seoOgpMedia']);
    }

    /**
     * SEOプラグイン有効時: seoMetaEnabled は true
     */
    public function test_seo_meta_enabled_when_provider_bound_and_enabled(): void
    {
        $provider = Mockery::mock(SeoMetaProviderInterface::class);
        $provider->shouldReceive('isEnabledForPlugin')->with('dixlase-pages')->andReturn(true);
        $this->app->instance(SeoMetaProviderInterface::class, $provider);

        $page = new DixlasePagesPage();
        $result = $this->prepareFormData->invoke($this->controller, $page);

        $this->assertTrue($result['seoMetaEnabled']);
    }

    /**
     * isEnabledForPlugin() が false の場合: 表示されない
     */
    public function test_seo_meta_disabled_when_capability_not_enabled(): void
    {
        $provider = Mockery::mock(SeoMetaProviderInterface::class);
        $provider->shouldReceive('isEnabledForPlugin')->with('dixlase-pages')->andReturn(false);
        $provider->shouldNotReceive('getMeta');
        $this->app->instance(SeoMetaProviderInterface::class, $provider);

        $page = new DixlasePagesPage();
        $result = $this->prepareFormData->invoke($this->controller, $page);

        $this->assertFalse($result['seoMetaEnabled']);
    }

    /**
     * 既存ページ編集時: getMeta() で既存メタ情報が取得される
     */
    public function test_existing_meta_loaded_for_existing_page(): void
    {
        $page = DixlasePagesPage::factory()->create();

        $meta = new SeoMetaDTO(description: 'Existing description', ogpMediaId: null);

        $provider = Mockery::mock(SeoMetaProviderInterface::class);
        $provider->shouldReceive('isEnabledForPlugin')->with('dixlase-pages')->andReturn(true);
        $provider->shouldReceive('getMeta')
            ->with('dixlase-pages', (string) $page->id)
            ->andReturn($meta);
        $this->app->instance(SeoMetaProviderInterface::class, $provider);

        $result = $this->prepareFormData->invoke($this->controller, $page);

        $this->assertTrue($result['seoMetaEnabled']);
        $this->assertSame($meta, $result['seoMeta']);
        $this->assertSame('Existing description', $result['seoMeta']->description);
    }

    /**
     * saveSeoMeta() がSEOプラグイン有効時に saveMeta を呼ぶ
     */
    public function test_save_seo_meta_calls_provider_when_enabled(): void
    {
        $page = DixlasePagesPage::factory()->create();

        $provider = Mockery::mock(SeoMetaProviderInterface::class);
        $provider->shouldReceive('isEnabledForPlugin')->with('dixlase-pages')->andReturn(true);
        $provider->shouldReceive('saveMeta')
            ->once()
            ->with(
                'dixlase-pages',
                (string) $page->id,
                Mockery::on(fn (SeoMetaDTO $dto) => $dto->description === 'New description' && $dto->ogpMediaId === null)
            );
        $this->app->instance(SeoMetaProviderInterface::class, $provider);

        $validated = [
            'seo_meta' => [
                'description' => 'New description',
            ],
        ];

        $this->saveSeoMeta->invoke($this->controller, $page, $validated);

        // Mockery の shouldReceive / shouldNotReceive はアサーションとしてカウントされないため明示
        $this->addToAssertionCount(1);
    }

    /**
     * saveSeoMeta() はSEOプラグイン無効時には何も呼ばない
     */
    public function test_save_seo_meta_skips_when_capability_not_enabled(): void
    {
        $page = DixlasePagesPage::factory()->create();

        $provider = Mockery::mock(SeoMetaProviderInterface::class);
        $provider->shouldReceive('isEnabledForPlugin')->with('dixlase-pages')->andReturn(false);
        $provider->shouldNotReceive('saveMeta');
        $this->app->instance(SeoMetaProviderInterface::class, $provider);

        $this->saveSeoMeta->invoke($this->controller, $page, [
            'seo_meta' => ['description' => 'x'],
        ]);

        $this->addToAssertionCount(1);
    }

    /**
     * saveSeoMeta() は Provider 未バインド時は何も呼ばない
     */
    public function test_save_seo_meta_skips_when_provider_not_bound(): void
    {
        $page = DixlasePagesPage::factory()->create();

        // バインドなし: 例外を投げずに何もしない
        $this->saveSeoMeta->invoke($this->controller, $page, [
            'seo_meta' => ['description' => 'x'],
        ]);

        $this->assertTrue(true); // 例外が発生しなければOK
    }

    /**
     * ページ強制削除時: deleteMeta がカスケード呼び出しされる
     */
    public function test_delete_meta_called_on_force_delete(): void
    {
        $page = DixlasePagesPage::factory()->create();

        $provider = Mockery::mock(SeoMetaProviderInterface::class);
        $provider->shouldReceive('deleteMeta')
            ->once()
            ->with('dixlase-pages', (string) $page->id);
        $this->app->instance(SeoMetaProviderInterface::class, $provider);

        $page->forceDelete();

        $this->addToAssertionCount(1);
    }

    /**
     * ページソフトデリート時: deleteMeta は呼ばれない
     */
    public function test_delete_meta_not_called_on_soft_delete(): void
    {
        $page = DixlasePagesPage::factory()->create();

        $provider = Mockery::mock(SeoMetaProviderInterface::class);
        $provider->shouldNotReceive('deleteMeta');
        $this->app->instance(SeoMetaProviderInterface::class, $provider);

        $page->delete(); // SoftDeletes: 物理削除ではない

        $this->addToAssertionCount(1);
    }
}
