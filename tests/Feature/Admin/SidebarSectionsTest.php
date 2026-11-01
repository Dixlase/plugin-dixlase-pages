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

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The editor's right column opens and closes in core's content editor
 * sections, as the blog's editor does (#113). Only "Title and slug" starts
 * open, publish settings come last, and a section holding a validation
 * error starts open.
 */
class SidebarSectionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Core registers the components:: namespace while booting a site,
        // which the test environment skips; the editor includes from it.
        $this->app['view']->addNamespace('components', resource_path('views/components'));
        $this->app['view']->addNamespace('dixlase-pages', base_path('plugins/DixlasePages/resources/views'));
        $this->app['translator']->addNamespace('dixlase-pages', base_path('plugins/DixlasePages/lang'));
        app()->setLocale('en');

        // The edit screen links to the page's revisions.
        Route::get('/pages/{page}/revisions', fn () => '')->name('dixlase-pages::admin.pages.revisions.index');
        Route::getRoutes()->refreshNameLookups();
    }

    public function test_only_title_and_slug_start_open_on_the_edit_screen(): void
    {
        $this->withViewErrors([]);

        $html = $this->renderEditor(isEditMode: true);

        $this->assertSame([
            'fas fa-heading' => true,
            'fas fa-circle-info' => false,
            'fas fa-clock-rotate-left' => false,
            'fas fa-calendar-check' => false,
        ], $this->sections($html));
        $this->assertStringContainsString('Page Information', $html);
    }

    public function test_the_create_screen_has_no_revisions_section(): void
    {
        $this->withViewErrors([]);

        $html = $this->renderEditor(isEditMode: false);

        $this->assertSame([
            'fas fa-heading' => true,
            'fas fa-circle-info' => false,
            'fas fa-calendar-check' => false,
        ], $this->sections($html));
        // The save method picker lives in the meta section on creation.
        $this->assertStringContainsString('name="storage_type"', $html);
    }

    public function test_the_closed_publish_section_shows_the_saved_status(): void
    {
        $this->withViewErrors([]);

        $html = $this->renderEditor(isEditMode: true, statusValue: 'draft');

        $this->assertMatchesRegularExpression('/Publish Settings<\/span>.*?>Draft<\/span>/s', $html);
    }

    public function test_a_section_holding_an_error_starts_open(): void
    {
        $this->withViewErrors(['published_at' => 'The publication date is required.']);

        $html = $this->renderEditor(isEditMode: true);

        $sections = $this->sections($html);
        $this->assertTrue($sections['fas fa-calendar-check']);
        $this->assertFalse($sections['fas fa-circle-info']);
        $this->assertStringContainsString('The publication date is required.', $html);
    }

    /**
     * Each section's icon, mapped to whether it starts open.
     *
     * @return array<string, bool>
     */
    private function sections(string $html): array
    {
        preg_match_all(
            '/x-data="contentEditorSidebarSection"\s+data-open="([01])".*?<i class="([^"]+?) w-4 /s',
            $html,
            $matches,
            PREG_SET_ORDER,
        );

        $sections = [];
        foreach ($matches as [, $open, $icon]) {
            $sections[$icon] = $open === '1';
        }

        return $sections;
    }

    private function renderEditor(bool $isEditMode, string $statusValue = 'draft'): string
    {
        $pageId = $isEditMode ? 7 : null;
        $statusOptions = ['draft' => 'Draft', 'published' => 'Published', 'scheduled' => 'Scheduled'];
        $storageOptions = ['database' => 'Database', 'file' => 'File'];

        return (string) $this->blade(
            '<x-dixlase-pages::page-content-editor :isEditMode="$isEditMode" :pageId="$pageId"'
            .' :statusValue="$statusValue" :statusOptions="$statusOptions" :storageOptions="$storageOptions" />',
            compact('isEditMode', 'pageId', 'statusValue', 'statusOptions', 'storageOptions'),
        );
    }
}
