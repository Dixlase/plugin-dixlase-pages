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
 * The page editor embeds the translate button on a saved page, and still
 * renders when no plugin provides the button.
 *
 * The button's view namespace is pointed at a stand-in that
 * prints its props, or at an empty directory, so each test controls
 * whether the component exists.
 */
class TranslateButtonEmbedTest extends TestCase
{
    private const BUTTON = 'data-test="translate-button"';

    protected function setUp(): void
    {
        parent::setUp();

        // Core registers the components:: namespace while booting a site,
        // which the test environment skips; the editor includes from it.
        $this->app['view']->addNamespace('components', resource_path('views/components'));
        $this->app['view']->addNamespace('dixlase-pages', base_path('plugins/DixlasePages/resources/views'));
        $this->app['translator']->addNamespace('dixlase-pages', base_path('plugins/DixlasePages/lang'));
        $this->withViewErrors([]);

        // The edit screen links to the page's revisions.
        Route::get('/pages/{page}/revisions', fn () => '')->name('dixlase-pages::admin.pages.revisions.index');
        Route::getRoutes()->refreshNameLookups();
    }

    public function test_edit_screen_of_a_saved_page_shows_the_button(): void
    {
        $this->useMultilingualViews(__DIR__.'/../../Fixtures/views/multilingual');

        $html = (string) $this->renderEditor(isEditMode: true, pageId: 7, langValue: 'ja');

        $this->assertStringContainsString(self::BUTTON.'>dixlase-pages:page|7|ja<', $html);
    }

    public function test_create_screen_shows_no_button(): void
    {
        $this->useMultilingualViews(__DIR__.'/../../Fixtures/views/multilingual');

        $html = (string) $this->renderEditor(isEditMode: false, pageId: null, langValue: 'ja');

        $this->assertStringNotContainsString(self::BUTTON, $html);
    }

    public function test_edit_screen_renders_without_the_multilingual_views(): void
    {
        $this->useMultilingualViews(__DIR__.'/../../Fixtures/views/none');

        $html = (string) $this->renderEditor(isEditMode: true, pageId: 7, langValue: 'ja');

        $this->assertStringContainsString('name="lang" value="ja"', $html);
        $this->assertStringNotContainsString(self::BUTTON, $html);
    }

    private function useMultilingualViews(string $path): void
    {
        $this->app['view']->getFinder()->replaceNamespace('dixlase-multilingual', [$path]);
    }

    private function renderEditor(bool $isEditMode, ?int $pageId, string $langValue): \Illuminate\Testing\TestView
    {
        return $this->blade(
            '<x-dixlase-pages::page-content-editor :isEditMode="$isEditMode" :pageId="$pageId" :langValue="$langValue" />',
            compact('isEditMode', 'pageId', 'langValue'),
        );
    }
}
