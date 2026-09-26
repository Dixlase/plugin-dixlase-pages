<?php

/**
 * This file is part of the Dixlase Pages plugin.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 */

namespace Plugins\DixlasePages\Tests\Feature\Admin;

use App\Actors\MemberActor;
use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
use App\Enums\MemberRole;
use App\Models\Member;
use App\Models\SiteSetting;
use App\Services\RevisionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Plugins\DixlasePages\App\Actions\Page\RestoreDixlasePagesPageRevisionAction;
use Plugins\DixlasePages\App\Http\Requests\Admin\DixlasePagesStorePageRequest;
use Plugins\DixlasePages\App\Http\Requests\Admin\DixlasePagesUpdatePageRequest;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Models\DixlasePagesPageRevision;
use Plugins\DixlasePages\App\Services\ScriptAuthoringPolicy;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Only ADMIN and above may put script-capable content on a page.
 *
 * The HTML editor and custom CSS / JS are served from the site's own origin,
 * so an editor's script would run in an administrator's browser with the
 * administrator's session. Simple mode additionally hides the HTML editor
 * and file storage from everyone; the server now enforces what the form hid.
 */
class ScriptAuthoringPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--path' => base_path('plugins/DixlasePages/database/migrations'),
            '--realpath' => true,
        ]);

        $this->app['translator']->addNamespace('dixlase-pages', base_path('plugins/DixlasePages/lang'));
    }

    private function member(MemberRole $role): Member
    {
        return Member::factory()->create(['role' => $role, 'status' => 1]);
    }

    private function advancedMode(): void
    {
        SiteSetting::setValue('admin_mode', '1');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: FormRequest, 1: \Illuminate\Validation\Validator}
     */
    private function validateStore(array $data): array
    {
        $request = DixlasePagesStorePageRequest::create('/pages', 'POST', $data + [
            'title' => 'Hello',
            'status' => 'draft',
            'lang' => 'en',
            'storage_type' => 'database',
        ]);
        $request->setContainer($this->app);

        (new ReflectionMethod($request, 'prepareForValidation'))->invoke($request);

        return [$request, Validator::make($request->all(), $request->rules())];
    }

    private function updateRequestFor(DixlasePagesPage $page): DixlasePagesUpdatePageRequest
    {
        $request = DixlasePagesUpdatePageRequest::create('/pages/'.$page->id, 'PUT', [
            'slug' => $page->slug,
            'title' => 'Hello',
            'status' => 'draft',
            'lang' => 'en',
            'storage_type' => 'file',
            'custom_js' => 'alert(1)',
            'custom_css' => 'body{}',
        ]);
        $request->setContainer($this->app);

        $route = new Route('PUT', 'pages/{page}', []);
        $route->bind($request);
        $route->setParameter('page', $page);
        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    public function test_only_admin_and_above_may_author_scripts(): void
    {
        $this->assertFalse(ScriptAuthoringPolicy::canAuthorScripts(null));
        $this->assertFalse(ScriptAuthoringPolicy::canAuthorScripts($this->member(MemberRole::CONTRIBUTOR)));
        $this->assertFalse(ScriptAuthoringPolicy::canAuthorScripts($this->member(MemberRole::EDITOR)));
        $this->assertTrue(ScriptAuthoringPolicy::canAuthorScripts($this->member(MemberRole::ADMIN)));
        $this->assertTrue(ScriptAuthoringPolicy::canAuthorScripts($this->member(MemberRole::SUPER_ADMIN)));
    }

    public function test_html_is_offered_only_to_admins_in_advanced_mode(): void
    {
        $editor = $this->member(MemberRole::EDITOR);
        $admin = $this->member(MemberRole::ADMIN);

        // Simple mode (the default): nobody gets HTML.
        $this->assertSame(['gui', 'markdown'], ScriptAuthoringPolicy::creatableEditorSlugs($admin));

        $this->advancedMode();
        $this->assertSame(['gui', 'markdown'], ScriptAuthoringPolicy::creatableEditorSlugs($editor));
        $this->assertSame(['gui', 'markdown', 'html'], ScriptAuthoringPolicy::creatableEditorSlugs($admin));
    }

    public function test_an_editor_cannot_create_an_html_page_even_in_advanced_mode(): void
    {
        $this->advancedMode();
        $this->actingAs($this->member(MemberRole::EDITOR), 'member');

        [, $validator] = $this->validateStore(['editor_type' => 'html', 'content' => '<script>x</script>']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('editor_type', $validator->errors()->toArray());
    }

    public function test_an_admin_can_create_an_html_page_in_advanced_mode(): void
    {
        $this->advancedMode();
        $this->actingAs($this->member(MemberRole::ADMIN), 'member');

        [$request, $validator] = $this->validateStore(['editor_type' => 'html', 'custom_js' => 'console.log(1)']);

        $this->assertFalse($validator->fails(), (string) json_encode($validator->errors()->toArray()));
        $this->assertSame('console.log(1)', $request->input('custom_js'));
    }

    public function test_simple_mode_refuses_html_and_file_storage_even_for_admins(): void
    {
        $this->actingAs($this->member(MemberRole::ADMIN), 'member');

        [$request, $validator] = $this->validateStore(['editor_type' => 'html', 'storage_type' => 'file']);

        $this->assertArrayHasKey('editor_type', $validator->errors()->toArray());
        $this->assertSame('database', $request->input('storage_type'));
    }

    public function test_custom_css_and_js_are_dropped_for_non_html_pages(): void
    {
        $this->actingAs($this->member(MemberRole::EDITOR), 'member');

        [$request, $validator] = $this->validateStore([
            'editor_type' => 'markdown',
            'custom_js' => 'alert(1)',
            'custom_css' => 'body{}',
        ]);

        $this->assertFalse($validator->fails(), (string) json_encode($validator->errors()->toArray()));
        $this->assertNull($request->input('custom_js'));
        $this->assertNull($request->input('custom_css'));
    }

    public function test_an_editor_may_not_update_an_html_page(): void
    {
        $page = DixlasePagesPage::factory()->create([
            'slug' => 'raw',
            'editor_type' => ContentEditorType::HTML,
            'storage_type' => ContentStorageType::DATABASE,
        ]);

        $this->actingAs($this->member(MemberRole::EDITOR), 'member');
        $this->assertFalse($this->updateRequestFor($page)->authorize());

        $this->actingAs($this->member(MemberRole::ADMIN), 'member');
        $this->assertTrue($this->updateRequestFor($page)->authorize());
    }

    public function test_an_editor_may_update_a_markdown_page_but_not_attach_scripts_or_move_it_to_files(): void
    {
        $page = DixlasePagesPage::factory()->create([
            'slug' => 'notes',
            'editor_type' => ContentEditorType::MARKDOWN,
            'storage_type' => ContentStorageType::DATABASE,
        ]);

        $this->actingAs($this->member(MemberRole::EDITOR), 'member');
        $request = $this->updateRequestFor($page);
        $this->assertTrue($request->authorize());

        (new ReflectionMethod($request, 'prepareForValidation'))->invoke($request);

        $this->assertNull($request->input('custom_js'));
        $this->assertNull($request->input('custom_css'));
        $this->assertSame('database', $request->input('storage_type'));
    }

    public function test_a_blade_revision_cannot_be_restored(): void
    {
        $admin = $this->member(MemberRole::ADMIN);
        $revision = new DixlasePagesPageRevision(['snapshot' => ['editor_type' => ContentEditorType::BLADE->value, 'content' => '{{ 7*6 }}']]);

        $action = new RestoreDixlasePagesPageRevisionAction($revision, $this->app->make(RevisionService::class));

        $this->expectException(ValidationException::class);
        (new ReflectionMethod($action, 'validate'))->invoke($action, new MemberActor($admin), []);
    }

    public function test_a_markdown_revision_passes_validation(): void
    {
        $admin = $this->member(MemberRole::ADMIN);
        $revision = new DixlasePagesPageRevision(['snapshot' => ['editor_type' => ContentEditorType::MARKDOWN->value]]);

        $action = new RestoreDixlasePagesPageRevisionAction($revision, $this->app->make(RevisionService::class));
        (new ReflectionMethod($action, 'validate'))->invoke($action, new MemberActor($admin), []);

        $this->addToAssertionCount(1);
    }
}
