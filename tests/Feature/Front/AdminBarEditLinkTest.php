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

use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
use App\Enums\MemberRole;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Models\Member;
use App\Services\Auth\SignedInMemberResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Models\DixlasePagesPageSetting;
use Tests\TestCase;

/**
 * The "Edit this page" link a page offers core's admin bar (#109)
 */
class AdminBarEditLinkTest extends TestCase
{
    use RefreshDatabase;

    private string $layoutDirectory;

    private string $pagesDirectory;

    private DixlasePagesPage $page;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--path' => base_path('plugins/DixlasePages/database/migrations'),
            '--realpath' => true,
        ]);

        $this->app['translator']->addNamespace('dixlase-pages', base_path('plugins/DixlasePages/lang'));
        $this->app['view']->addNamespace('dixlase-pages', base_path('plugins/DixlasePages/resources/views'));
        app()->setLocale('en');

        // A bare layout that prints the admin bar's sections the way core
        // reads them.
        $this->layoutDirectory = storage_path('framework/testing/pages-layout-'.uniqid());
        File::ensureDirectoryExists($this->layoutDirectory.'/layouts');
        File::put($this->layoutDirectory.'/layouts/app.blade.php', "<html><body><nav>[@yield('admin_bar_edit_url')|@yield('admin_bar_edit_label')]</nav>@yield('content')</body></html>");
        $this->app['view']->replaceNamespace('themes', [$this->layoutDirectory]);

        $this->pagesDirectory = DixlasePagesPageSetting::getValue('route_slug', 'page');

        Route::middleware('plugin')->prefix('admin-custom')->group(base_path('plugins/DixlasePages/routes/admin.php'));
        Route::middleware('web')->group(base_path('plugins/DixlasePages/routes/web.php'));
        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMaintenanceMode::class,
        ]);

        $this->page = DixlasePagesPage::factory()->published()->create([
            'slug' => 'about',
            'storage_type' => ContentStorageType::DATABASE,
            'editor_type' => ContentEditorType::HTML,
            'lang' => 'en',
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->layoutDirectory);

        parent::tearDown();
    }

    public function test_an_editor_signed_in_to_the_admin_gets_the_edit_link(): void
    {
        $this->signInToTheAdmin(MemberRole::EDITOR);

        $this->get('/'.$this->pagesDirectory.'/about')->assertOk()
            ->assertSee('<nav>['.route('dixlase-pages::admin.pages.edit', ['page' => $this->page->id]).'|Edit this page]</nav>', false);
    }

    public function test_a_member_who_may_not_edit_pages_gets_no_link(): void
    {
        $this->signInToTheAdmin(MemberRole::CONTRIBUTOR);

        $this->get('/'.$this->pagesDirectory.'/about')->assertOk()->assertSee('<nav>[|]</nav>', false);
    }

    public function test_a_visitor_gets_no_link(): void
    {
        $this->signInToTheAdmin(null);

        $this->get('/'.$this->pagesDirectory.'/about')->assertOk()->assertSee('<nav>[|]</nav>', false);
    }

    /**
     * The front request carries no admin login, so core's resolver is
     * what reports one; stand in for it.
     */
    private function signInToTheAdmin(?MemberRole $role): void
    {
        $member = $role === null ? null : Member::factory()->create(['role' => $role, 'status' => 1]);

        $this->app->instance(SignedInMemberResolver::class, new class($member) extends SignedInMemberResolver
        {
            public function __construct(private readonly ?Member $member) {}

            public function resolve(): ?Member
            {
                return $this->member;
            }
        });
    }
}
