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

namespace Plugins\DixlasePages\Tests\Unit;

use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
use App\Enums\MemberRole;
use App\Models\Member;
use App\Services\Editor\EditorManager;
use App\Services\Plugin\PluginServiceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Services\DixlasePagesPageRenderer;
use Tests\TestCase;

/**
 * DixlasePagesPageRenderer — the page preparation the display route used to
 * hold inline (#127 step 1).
 *
 * These exercise the renderer directly, with no route and no view, which is
 * the point of the extraction: the member is a parameter, so a caller that
 * is not an HTTP request can ask for the same work.
 */
class DixlasePagesPageRendererTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--path' => base_path('plugins/DixlasePages/database/migrations'),
            '--realpath' => true,
        ]);

        app()->setLocale('en');
    }

    private function renderer(): DixlasePagesPageRenderer
    {
        return app(DixlasePagesPageRenderer::class);
    }

    private function page(string $state, array $overrides = []): DixlasePagesPage
    {
        return DixlasePagesPage::factory()->{$state}()->create(array_merge([
            'slug' => 'about',
            'lang' => 'en',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
        ], $overrides));
    }

    // =========================================================================
    // Declining
    // =========================================================================

    public function test_an_unknown_path_is_declined(): void
    {
        $this->assertNull($this->renderer()->renderPath('no-such-page', null));
    }

    /**
     * Null means "a public visitor", so an unpublished page is not found at
     * all. This is the direction a caller that forgets to pass the member
     * falls in, which is why the parameter has no default.
     */
    public function test_a_visitor_does_not_reach_an_unpublished_page(): void
    {
        $this->page('draft');

        $this->assertNull($this->renderer()->renderPath('about', null));
    }

    public function test_a_member_signed_in_to_the_admin_reaches_an_unpublished_page(): void
    {
        $this->page('draft');
        $member = Member::factory()->create(['role' => MemberRole::EDITOR, 'status' => 1]);

        $this->assertNotNull($this->renderer()->renderPath('about', $member));
    }

    // =========================================================================
    // Whether the response may be stored
    // =========================================================================

    public function test_a_published_page_is_storable(): void
    {
        $this->page('published');

        $this->assertTrue($this->renderer()->renderPath('about', null)->storable);
    }

    public function test_an_unpublished_page_is_not_storable(): void
    {
        $this->page('draft');
        $member = Member::factory()->create(['role' => MemberRole::EDITOR, 'status' => 1]);

        $this->assertFalse($this->renderer()->renderPath('about', $member)->storable);
    }

    /**
     * render() renders whatever it is handed: visibility was settled when
     * the page was resolved. A caller holding a page it resolved itself is
     * therefore not silently given an empty result.
     */
    public function test_a_resolved_page_is_rendered_whatever_its_state(): void
    {
        $rendered = $this->renderer()->render($this->page('draft'), null);

        $this->assertFalse($rendered->storable);
        $this->assertSame('about', $rendered->page->slug);
    }

    // =========================================================================
    // GUI editor conversion
    // =========================================================================

    public function test_gui_content_is_converted_and_the_editor_type_follows(): void
    {
        $this->page('published', [
            'editor_type' => ContentEditorType::GUI,
            'content' => '{"blocks":[]}',
        ]);

        $this->app->instance(EditorManager::class, new class(app(PluginServiceResolver::class)) extends EditorManager
        {
            public function renderContent(string $editorTypeSlug, string $content): string
            {
                return '<p>rendered by the gui editor</p>';
            }
        });

        $rendered = $this->renderer()->renderPath('about', null);

        $this->assertSame('html', $rendered->editorType);
        $this->assertSame('<p>rendered by the gui editor</p>', $rendered->content);
    }

    /**
     * With no GUI editor installed, EditorManager::renderContent() returns
     * an empty string. The page must keep its own content rather than go
     * blank, so the guard on the empty string is load-bearing.
     */
    public function test_gui_content_is_left_alone_when_no_editor_answers(): void
    {
        $this->page('published', [
            'editor_type' => ContentEditorType::GUI,
            'content' => '{"blocks":[]}',
        ]);

        $this->app->instance(EditorManager::class, new class(app(PluginServiceResolver::class)) extends EditorManager
        {
            public function renderContent(string $editorTypeSlug, string $content): string
            {
                return '';
            }
        });

        $rendered = $this->renderer()->renderPath('about', null);

        $this->assertSame('gui', $rendered->editorType);
        $this->assertSame('{"blocks":[]}', $rendered->content);
    }

    // =========================================================================
    // The view contract
    // =========================================================================

    /**
     * The front view's variables are named in one place. A key added here
     * without the view being taught about it, or dropped while the view
     * still reads it, is the failure this pins down.
     */
    public function test_the_view_data_carries_exactly_what_the_front_view_expects(): void
    {
        $this->page('published');

        $this->assertSame([
            'page',
            'editorType',
            'content',
            'hasCustomCss',
            'hasCustomJs',
            'customAssetVersion',
            'adminEditUrl',
        ], array_keys($this->renderer()->renderPath('about', null)->toViewData()));
    }

    public function test_a_visitor_gets_no_admin_edit_link(): void
    {
        $this->page('published');

        $this->assertNull($this->renderer()->renderPath('about', null)->adminEditUrl);
    }

    /**
     * The custom-asset version is what busts the cache on the page's own
     * CSS and JS URLs, so it has to move when the page is saved.
     */
    public function test_the_custom_asset_version_follows_the_page(): void
    {
        $page = $this->page('published');

        $this->assertSame(
            $page->updated_at->timestamp,
            $this->renderer()->renderPath('about', null)->customAssetVersion
        );
    }
}
