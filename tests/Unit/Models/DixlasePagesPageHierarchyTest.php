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

/**
 * Coverage for the parent/child hierarchy feature: relations, URL
 * composition, depth/cycle helpers, the resolvePath() walker, and the
 * orphan-promotion lifecycle hook.
 */
class DixlasePagesPageHierarchyTest extends TestCase
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
            'lang' => 'en',
            'title' => 'Test Page',
            'content' => '<p>Test content</p>',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'status' => ContentStatus::PUBLISHED,
        ], $overrides));
    }

    public function test_page_can_be_created_with_parent_id(): void
    {
        $parent = $this->createPage(['slug' => 'parent']);
        $child = $this->createPage(['slug' => 'child', 'parent_id' => $parent->id]);

        $this->assertSame((int) $parent->id, (int) $child->parent_id);
    }

    public function test_parent_relation_returns_the_parent_page(): void
    {
        $parent = $this->createPage(['slug' => 'parent']);
        $child = $this->createPage(['slug' => 'child', 'parent_id' => $parent->id]);

        $this->assertSame((int) $parent->id, (int) $child->parent->id);
    }

    public function test_children_relation_returns_direct_descendants(): void
    {
        $parent = $this->createPage(['slug' => 'parent']);
        $this->createPage(['slug' => 'child-a', 'parent_id' => $parent->id]);
        $this->createPage(['slug' => 'child-b', 'parent_id' => $parent->id]);

        $childSlugs = $parent->children()->pluck('slug')->all();

        sort($childSlugs);
        $this->assertSame(['child-a', 'child-b'], $childSlugs);
    }

    public function test_top_level_scope_excludes_nested_pages(): void
    {
        $parent = $this->createPage(['slug' => 'parent']);
        $this->createPage(['slug' => 'child', 'parent_id' => $parent->id]);

        $topSlugs = DixlasePagesPage::topLevel()->pluck('slug')->all();

        $this->assertSame(['parent'], $topSlugs);
    }

    public function test_depth_is_zero_for_top_level_pages(): void
    {
        $page = $this->createPage();

        $this->assertSame(0, $page->depth());
    }

    public function test_depth_counts_ancestors(): void
    {
        $a = $this->createPage(['slug' => 'a']);
        $b = $this->createPage(['slug' => 'b', 'parent_id' => $a->id]);
        $c = $this->createPage(['slug' => 'c', 'parent_id' => $b->id]);

        $this->assertSame(2, $c->depth());
    }

    public function test_ancestors_and_self_returns_root_to_self_order(): void
    {
        $a = $this->createPage(['slug' => 'a']);
        $b = $this->createPage(['slug' => 'b', 'parent_id' => $a->id]);
        $c = $this->createPage(['slug' => 'c', 'parent_id' => $b->id]);

        $chain = array_map(static fn ($p) => $p->slug, $c->ancestorsAndSelf());

        $this->assertSame(['a', 'b', 'c'], $chain);
    }

    public function test_path_segments_returns_slugs_from_root(): void
    {
        $a = $this->createPage(['slug' => 'a']);
        $b = $this->createPage(['slug' => 'b', 'parent_id' => $a->id]);

        $this->assertSame(['a', 'b'], $b->pathSegments());
    }

    public function test_page_url_includes_full_hierarchical_path(): void
    {
        $a = $this->createPage(['slug' => 'a']);
        $b = $this->createPage(['slug' => 'b', 'parent_id' => $a->id]);

        $this->assertStringEndsWith('/a/b', $b->page_url);
    }

    public function test_subtree_max_depth_walks_descendants(): void
    {
        $a = $this->createPage(['slug' => 'a']);
        $b = $this->createPage(['slug' => 'b', 'parent_id' => $a->id]);
        $this->createPage(['slug' => 'c', 'parent_id' => $b->id]);

        $this->assertSame(2, $a->subtreeMaxDepth());
        $this->assertSame(1, $b->subtreeMaxDepth());
    }

    public function test_subtree_ids_includes_self_and_all_descendants(): void
    {
        $a = $this->createPage(['slug' => 'a']);
        $b = $this->createPage(['slug' => 'b', 'parent_id' => $a->id]);
        $c = $this->createPage(['slug' => 'c', 'parent_id' => $b->id]);

        $ids = $a->subtreeIds();
        sort($ids);
        $expected = [(int) $a->id, (int) $b->id, (int) $c->id];
        sort($expected);
        $this->assertSame($expected, $ids);
    }

    public function test_resolve_path_finds_top_level_page(): void
    {
        $this->createPage(['slug' => 'about']);

        $page = DixlasePagesPage::resolvePath('about', 'en', publishedOnly: true);

        $this->assertNotNull($page);
        $this->assertSame('about', $page->slug);
    }

    public function test_resolve_path_walks_nested_segments(): void
    {
        $parent = $this->createPage(['slug' => 'parent']);
        $this->createPage(['slug' => 'child', 'parent_id' => $parent->id]);

        $page = DixlasePagesPage::resolvePath('parent/child', 'en', publishedOnly: true);

        $this->assertNotNull($page);
        $this->assertSame('child', $page->slug);
    }

    public function test_resolve_path_returns_null_when_a_segment_is_missing(): void
    {
        $this->createPage(['slug' => 'parent']);

        $page = DixlasePagesPage::resolvePath('parent/missing', 'en', publishedOnly: true);

        $this->assertNull($page);
    }

    public function test_resolve_path_excludes_drafts_when_published_only(): void
    {
        $this->createPage(['slug' => 'draft', 'status' => ContentStatus::DRAFT]);

        $publicView = DixlasePagesPage::resolvePath('draft', 'en', publishedOnly: true);
        $previewView = DixlasePagesPage::resolvePath('draft', 'en', publishedOnly: false);

        $this->assertNull($publicView);
        $this->assertNotNull($previewView);
    }

    public function test_soft_deleting_a_parent_promotes_children_to_top_level(): void
    {
        $parent = $this->createPage(['slug' => 'parent']);
        $child = $this->createPage(['slug' => 'child', 'parent_id' => $parent->id]);

        $parent->delete();

        $this->assertNull($child->fresh()->parent_id);
    }

    public function test_force_deleting_a_parent_also_promotes_children(): void
    {
        $parent = $this->createPage(['slug' => 'parent']);
        $child = $this->createPage(['slug' => 'child', 'parent_id' => $parent->id]);

        $parent->forceDelete();

        $this->assertNull($child->fresh()->parent_id);
    }
}
