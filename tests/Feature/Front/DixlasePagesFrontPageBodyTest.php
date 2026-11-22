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

use App\Contracts\Front\FrontPageBodyProviderInterface;
use App\DTO\Front\FrontPageBodyContext;
use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Services\Plugin\PluginServiceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Models\DixlasePagesPageSetting;
use Plugins\DixlasePages\App\Providers\DixlasePagesServiceProvider;
use Plugins\DixlasePages\App\Services\DixlasePagesFrontPageBodyProvider;
use Tests\TestCase;

/**
 * A published page offered as the body of a theme's front page (#127 step 2).
 *
 * The provider is exercised directly rather than through a theme, because
 * what a theme does with the result is the theme's test. What is pinned here
 * is the contract: which choices are offered, when the provider declines, and
 * that the body it returns is a body — no layout, no container of its own,
 * and the heading suppressed when the surface already carries one.
 */
class DixlasePagesFrontPageBodyTest extends TestCase
{
    use RefreshDatabase;

    protected string $pagesDirectory;

    protected string $layoutDirectory;

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

        $this->app['view']->addNamespace(
            'dixlase-pages',
            base_path('plugins/DixlasePages/resources/views')
        );

        // register() is what carries the capability tag. A bare test app does
        // not boot the plugin's own ServiceProvider, so a provider that is
        // only reachable through that tag has to be registered here, as it is
        // on a site.
        (new DixlasePagesServiceProvider($this->app))->register();

        // Neither check has anything to say about a page; left on, both
        // redirect the front route away before it renders.
        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMaintenanceMode::class,
        ]);

        $this->pagesDirectory = DixlasePagesPageSetting::getValue('route_slug', 'page');

        // The custom-asset URLs the provider returns are built with route(),
        // so the plugin's own front routes have to be registered. They are
        // not at boot, because the settings table does not exist yet then.
        Route::middleware('web')->group(base_path('plugins/DixlasePages/routes/web.php'));
        $routes = app('router')->getRoutes();
        $routes->refreshNameLookups();
        $routes->refreshActionLookups();

        // A throwaway stand-in for the active theme's layout, so the page
        // route can be exercised without a theme checked out — and without a
        // test reaching into a real repository directory.
        $this->layoutDirectory = storage_path('framework/testing/pages-layout-'.uniqid());
        File::ensureDirectoryExists($this->layoutDirectory.'/layouts');
        File::put(
            $this->layoutDirectory.'/layouts/app.blade.php',
            "<html><body>@yield('content')</body></html>"
        );
        $this->app['view']->replaceNamespace('themes', [$this->layoutDirectory]);

        App::setLocale('en');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->layoutDirectory);

        parent::tearDown();
    }

    private function provider(): DixlasePagesFrontPageBodyProvider
    {
        return app(DixlasePagesFrontPageBodyProvider::class);
    }

    private function publishPage(array $overrides = []): DixlasePagesPage
    {
        return DixlasePagesPage::factory()->published()->create(array_merge([
            'slug' => 'about',
            'title' => 'About us',
            'lang' => 'en',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'content' => '<p>Who we are</p>',
        ], $overrides));
    }

    private function render(DixlasePagesPage $page, ?FrontPageBodyContext $context = null): ?\App\DTO\Front\FrontPageBodyResult
    {
        return $this->provider()->renderFrontPageBody(
            (string) $page->getKey(),
            $context ?? new FrontPageBodyContext,
        );
    }

    // =========================================================================
    // Registration
    // =========================================================================

    /**
     * The tag is what a theme reaches the provider through. It is set in the
     * service provider's register(), not boot(): the resolver reads the tag,
     * and a capability tagged after the container has been asked for it is
     * simply absent.
     */
    public function test_the_provider_is_reachable_through_the_capability_tag(): void
    {
        $resolved = app(PluginServiceResolver::class)
            ->resolveAll(FrontPageBodyProviderInterface::class);

        $instances = array_map(
            fn ($result) => $result->instance,
            array_filter($resolved, fn ($result) => $result->isResolved())
        );

        $this->assertNotEmpty($instances, 'No front-page-body provider resolved.');

        $ours = array_filter(
            $instances,
            fn ($instance) => $instance instanceof DixlasePagesFrontPageBodyProvider
        );

        $this->assertCount(
            1,
            $ours,
            "This plugin's provider is not among the resolved capabilities."
        );
    }

    public function test_the_plugin_slug_matches_the_manifest(): void
    {
        $manifest = json_decode(
            file_get_contents(base_path('plugins/DixlasePages/plugin.json')),
            true
        );

        $this->assertSame($manifest['slug'], $this->provider()->getPluginSlug());
        $this->assertContains('front-page-body', $manifest['capabilities']);
    }

    public function test_the_group_label_is_translated(): void
    {
        $this->assertSame('Pages', $this->provider()->getFrontPageBodyLabel());
    }

    // =========================================================================
    // The choices offered
    // =========================================================================

    public function test_nothing_is_offered_when_no_page_is_published(): void
    {
        DixlasePagesPage::factory()->draft()->create(['slug' => 'secret', 'lang' => 'en']);

        $this->assertSame([], $this->provider()->availableFrontPageBodies());
    }

    public function test_only_published_pages_are_offered(): void
    {
        $published = $this->publishPage();
        DixlasePagesPage::factory()->draft()->create(['slug' => 'secret', 'lang' => 'en']);

        $offered = $this->provider()->availableFrontPageBodies();

        $this->assertCount(1, $offered);
        $this->assertSame((string) $published->getKey(), $offered[0]->id);
    }

    public function test_pages_are_offered_newest_first(): void
    {
        $older = $this->publishPage([
            'slug' => 'older', 'title' => 'Older', 'created_at' => now()->subDay(),
        ]);
        $newer = $this->publishPage([
            'slug' => 'newer', 'title' => 'Newer', 'created_at' => now(),
        ]);

        $offered = $this->provider()->availableFrontPageBodies();

        $this->assertSame(
            [(string) $newer->getKey(), (string) $older->getKey()],
            array_map(fn ($dto) => $dto->id, $offered)
        );
    }

    public function test_the_limit_is_honoured(): void
    {
        $this->publishPage(['slug' => 'one', 'title' => 'One']);
        $this->publishPage(['slug' => 'two', 'title' => 'Two']);

        $this->assertCount(1, $this->provider()->availableFrontPageBodies(1));
    }

    /**
     * A limit below one is a caller asking for nothing, which is answered
     * with nothing rather than with the whole table.
     */
    public function test_a_limit_below_one_offers_nothing(): void
    {
        $this->publishPage();

        $this->assertSame([], $this->provider()->availableFrontPageBodies(0));
    }

    public function test_a_choice_carries_the_path_in_its_meta_and_description(): void
    {
        $this->publishPage(['slug' => 'about', 'title' => 'About us']);

        $dto = $this->provider()->availableFrontPageBodies()[0];

        $this->assertSame('about', $dto->meta['path']);
        $this->assertSame('about', $dto->meta['slug']);
        $this->assertSame('en', $dto->meta['lang']);
        $this->assertSame('about', $dto->description);
    }

    public function test_a_unique_title_is_the_whole_label(): void
    {
        $this->publishPage(['slug' => 'about', 'title' => 'About us']);

        $this->assertSame('About us', $this->provider()->availableFrontPageBodies()[0]->label);
    }

    /**
     * The label is all a chooser gets: core's FrontPageBodyResolver folds
     * each DTO down to `choice => label` in both groupedBodies() and
     * availableBodies(), dropping the description and the meta before a theme
     * sees them. Two pages with one title would be indistinguishable in any
     * chooser built on those, so the path goes into the label as well.
     */
    public function test_a_repeated_title_gets_its_path_in_the_label(): void
    {
        $this->publishPage(['slug' => 'about', 'title' => 'About']);
        $this->publishPage(['slug' => 'about-us', 'title' => 'About']);

        $labels = array_map(
            fn ($dto) => $dto->label,
            $this->provider()->availableFrontPageBodies()
        );
        sort($labels);

        $this->assertSame(['About (about)', 'About (about-us)'], $labels);
    }

    /**
     * The legacy storage shape keeps one row per language, so the same page
     * can appear twice with the same title and the same path. The language
     * is what tells those apart.
     */
    public function test_a_repeated_title_and_path_gets_the_language_too(): void
    {
        $this->publishPage(['slug' => 'about', 'title' => 'About', 'lang' => 'en']);
        $this->publishPage(['slug' => 'about', 'title' => 'About', 'lang' => 'ja']);

        $labels = array_map(
            fn ($dto) => $dto->label,
            $this->provider()->availableFrontPageBodies()
        );
        sort($labels);

        $this->assertSame(['About (about) [en]', 'About (about) [ja]'], $labels);
    }

    // =========================================================================
    // Declining
    // =========================================================================

    public function test_an_id_that_is_not_a_page_key_is_declined(): void
    {
        $this->publishPage();

        $this->assertNull($this->provider()->renderFrontPageBody('top', new FrontPageBodyContext));
        $this->assertNull($this->provider()->renderFrontPageBody('', new FrontPageBodyContext));
    }

    public function test_a_page_that_no_longer_exists_is_declined(): void
    {
        $page = $this->publishPage();
        $id = (string) $page->getKey();
        $page->delete();

        $this->assertNull($this->provider()->renderFrontPageBody($id, new FrontPageBodyContext));
    }

    /**
     * The whole reason the result DTO needs no response-header field: an
     * operator can only ever have chosen a published page, and one that is
     * later unpublished stops rendering rather than being served privately.
     */
    public function test_a_page_that_was_unpublished_is_declined(): void
    {
        $page = $this->publishPage();
        $page->update(['status' => \App\Enums\ContentStatus::DRAFT, 'published_at' => null]);

        $this->assertNull($this->render($page->fresh()));
    }

    // =========================================================================
    // The body
    // =========================================================================

    public function test_the_body_is_the_page_content(): void
    {
        $page = $this->publishPage(['content' => '<p>Who we are</p>']);

        $result = $this->render($page);

        $this->assertNotNull($result);
        $this->assertStringContainsString('Who we are', $result->html());
    }

    /**
     * A body is placed inside the theme's own layout, so it must bring none
     * of its own. A stray @extends here would render a whole second document
     * into the middle of the theme's page.
     */
    public function test_the_body_carries_no_page_layout(): void
    {
        $html = $this->render($this->publishPage())->html();

        $this->assertStringNotContainsString('<html', $html);
        $this->assertStringNotContainsString('<body', $html);
        $this->assertStringNotContainsString('</head>', $html);
    }

    /**
     * The theme opens `container mx-auto px-4` around the body and applies no
     * prose of its own. A container here would nest inside that one and add a
     * second set of side padding, so the body brings prose and nothing else.
     */
    public function test_the_body_brings_prose_but_not_a_container(): void
    {
        $html = $this->render($this->publishPage())->html();

        $this->assertStringContainsString('prose', $html);
        $this->assertStringNotContainsString('container mx-auto', $html);
    }

    /**
     * `.dixlase-page` is the hook a page's custom CSS is written against. It
     * has to survive on this surface too, or every site's custom CSS stops
     * applying the moment the page is shown on the front page.
     */
    public function test_the_custom_css_hook_class_is_present(): void
    {
        $this->assertStringContainsString(
            'dixlase-page',
            $this->render($this->publishPage())->html()
        );
    }

    public function test_the_heading_is_shown_when_the_surface_asks_for_it(): void
    {
        $page = $this->publishPage(['title' => 'About us']);

        $html = $this->render($page, new FrontPageBodyContext(showHeading: true))->html();

        $this->assertStringContainsString('<h1', $html);
        $this->assertStringContainsString('About us', $html);
    }

    /**
     * A theme with a hero above the body already carries the heading.
     * Repeating it there is the visible defect this flag exists for.
     */
    public function test_the_heading_is_suppressed_but_the_content_is_not(): void
    {
        $page = $this->publishPage(['title' => 'About us', 'content' => '<p>Who we are</p>']);

        $html = $this->render($page, new FrontPageBodyContext(showHeading: false))->html();

        $this->assertStringNotContainsString('<h1', $html);
        $this->assertStringContainsString('Who we are', $html);
    }

    public function test_the_title_is_the_page_title(): void
    {
        $result = $this->render($this->publishPage(['title' => 'About us']));

        $this->assertSame('About us', $result->title);
    }

    /**
     * Markdown goes through core's content renderer, exactly as it does on
     * the page's own route, so the two surfaces cannot drift apart.
     */
    public function test_a_markdown_page_is_rendered_as_markdown(): void
    {
        $page = $this->publishPage([
            'editor_type' => ContentEditorType::MARKDOWN,
            'content' => "## A heading\n\nSome text.",
        ]);

        $html = $this->render($page)->html();

        $this->assertStringContainsString('A heading', $html);
        $this->assertStringNotContainsString('## A heading', $html);
    }

    // =========================================================================
    // Assets
    // =========================================================================

    /**
     * The page view pushes these onto the theme's stacks itself. A body
     * cannot push, so they travel in the result instead.
     */
    public function test_a_page_with_custom_css_hands_its_url_to_the_theme(): void
    {
        $page = $this->publishPage(['custom_css' => '.dixlase-page { color: red; }']);

        $result = $this->render($page);

        $this->assertCount(1, $result->styleUrls);
        $this->assertStringContainsString($this->pagesDirectory.'/about', $result->styleUrls[0]);
        $this->assertStringContainsString('custom-style', $result->styleUrls[0]);
        $this->assertStringContainsString('?v=', $result->styleUrls[0]);
    }

    public function test_a_page_with_custom_js_hands_its_url_to_the_theme(): void
    {
        $page = $this->publishPage(['custom_js' => 'console.log(1);']);

        $result = $this->render($page);

        $this->assertCount(1, $result->scriptUrls);
        $this->assertStringContainsString('custom-script', $result->scriptUrls[0]);
    }

    public function test_a_page_without_custom_assets_hands_over_nothing(): void
    {
        $result = $this->render($this->publishPage());

        $this->assertSame([], $result->styleUrls);
        $this->assertSame([], $result->scriptUrls);
    }

    // =========================================================================
    // Locale
    // =========================================================================

    /**
     * The caller states the locale, and the locale it was called in is
     * restored afterwards whatever the render did.
     */
    public function test_the_app_locale_is_restored_after_rendering(): void
    {
        $page = $this->publishPage();
        App::setLocale('en');

        $this->render($page, new FrontPageBodyContext(locale: 'ja'));

        $this->assertSame('en', App::getLocale());
    }

    // =========================================================================
    // The page's own route still works
    // =========================================================================

    /**
     * The body was extracted out of front/page.blade.php, which now includes
     * it. The route is the surface that regression would show up on, and it
     * keeps the chrome the body no longer carries.
     */
    public function test_the_pages_own_route_still_renders_the_whole_page(): void
    {
        $this->publishPage(['slug' => 'about', 'title' => 'About us', 'content' => '<p>Who we are</p>']);

        $response = $this->get('/'.$this->pagesDirectory.'/about');

        $response->assertStatus(200);
        $response->assertSee('About us');
        $response->assertSee('Who we are', false);
        $response->assertSee('container mx-auto', false);
    }
}
