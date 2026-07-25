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

namespace Plugins\DixlasePages\Tests\Unit\Models;

use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Tests\TestCase;

class DixlasePagesPageModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--path' => base_path('plugins/DixlasePages/database/migrations'),
            '--realpath' => true,
        ]);
    }

    private function createPage(array $overrides = []): DixlasePagesPage
    {
        return DixlasePagesPage::create(array_merge([
            'slug' => 'test-page',
            'lang' => 'ja',
            'title' => 'Test Page',
            'content' => '<p>Test content</p>',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'status' => ContentStatus::PUBLISHED,
        ], $overrides));
    }

    // =========================================================================
    // 基本操作
    // =========================================================================

    public function test_page_can_be_created(): void
    {
        $page = $this->createPage();

        $this->assertDatabaseHas('plg_dixlase_pages', ['slug' => 'test-page']);
    }

    /**
     * Source language can be changed after creation so an operator can
     * relocate a row between locale-prefixed front URLs without recreating
     * it.
     */
    public function test_lang_is_updatable_after_creation(): void
    {
        $page = $this->createPage(['lang' => 'en']);

        $page->update(['lang' => 'ja']);

        $this->assertSame('ja', $page->fresh()->lang);
    }

    public function test_casts(): void
    {
        $page = $this->createPage();
        $page->refresh();

        $this->assertInstanceOf(ContentStorageType::class, $page->storage_type);
        $this->assertInstanceOf(ContentEditorType::class, $page->editor_type);
    }

    public function test_published_at_cast_to_datetime(): void
    {
        $page = $this->createPage(['published_at' => now()]);
        $page->refresh();

        $this->assertInstanceOf(\Carbon\Carbon::class, $page->published_at);
    }

    // =========================================================================
    // isPublished
    // =========================================================================

    public function test_published_page_is_published(): void
    {
        $page = $this->createPage(['status' => ContentStatus::PUBLISHED]);

        $this->assertTrue($page->isPublished());
    }

    public function test_draft_page_is_not_published(): void
    {
        $page = $this->createPage(['status' => ContentStatus::DRAFT]);

        $this->assertFalse($page->isPublished());
    }

    public function test_scheduled_page_with_past_date_is_published(): void
    {
        $page = $this->createPage([
            'status' => ContentStatus::SCHEDULED,
            'published_at' => now()->subDay(),
        ]);

        $this->assertTrue($page->isPublished());
    }

    public function test_scheduled_page_with_future_date_is_not_published(): void
    {
        $page = $this->createPage([
            'status' => ContentStatus::SCHEDULED,
            'published_at' => now()->addDay(),
        ]);

        $this->assertFalse($page->isPublished());
    }

    // =========================================================================
    // スコープ
    // =========================================================================

    public function test_for_lang_scope(): void
    {
        $this->createPage(['slug' => 'ja-page', 'lang' => 'ja']);
        $this->createPage(['slug' => 'en-page', 'lang' => 'en']);

        $jaPages = DixlasePagesPage::forLang('ja')->get();

        $this->assertEquals(1, $jaPages->count());
        $this->assertEquals('ja-page', $jaPages->first()->slug);
    }

    public function test_draft_scope(): void
    {
        $this->createPage(['slug' => 'published', 'status' => ContentStatus::PUBLISHED]);
        $this->createPage(['slug' => 'draft', 'status' => ContentStatus::DRAFT]);

        $drafts = DixlasePagesPage::draft()->get();

        $this->assertEquals(1, $drafts->count());
        $this->assertEquals('draft', $drafts->first()->slug);
    }

    // =========================================================================
    // ソフトデリート
    // =========================================================================

    public function test_soft_delete(): void
    {
        $page = $this->createPage();
        $id = $page->id;

        $page->delete();

        $this->assertSoftDeleted('plg_dixlase_pages', ['id' => $id]);
    }

    // =========================================================================
    // コンテンツ
    // =========================================================================

    public function test_page_stores_custom_css_and_js(): void
    {
        $page = $this->createPage([
            'custom_css' => 'body { color: red; }',
            'custom_js' => 'console.log("test");',
        ]);

        $page->refresh();
        $this->assertEquals('body { color: red; }', $page->custom_css);
        $this->assertEquals('console.log("test");', $page->custom_js);
    }
}
