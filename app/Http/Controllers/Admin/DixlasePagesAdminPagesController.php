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

namespace Plugins\DixlasePages\App\Http\Controllers\Admin;

use App\Actions\Content\PublishFileContentAction;
use App\Actors\MemberActor;
use App\Contracts\PluginIntegration\SeoMetaProviderInterface;
use App\Contracts\Repositories\MediaRepositoryInterface;
use App\DTO\PluginIntegration\SeoMetaDTO;
use App\Enums\ContentEditorType;
use App\Enums\ContentPublishMode;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Enums\MemberRole;
use App\Facades\SiteSettings;
use App\Helpers\AdminHelper;
use App\Helpers\LocaleHelper;
use App\Presenters\Admin\RevisionDiffPresenter;
use App\Services\Content\FileContentPublisher;
use App\Services\ContentPreviewService;
use App\Services\RevisionService;
use App\Traits\AdminInterfaceTrait;
use App\Traits\AdminLoggedInTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Plugins\DixlasePages\App\Actions\Page\CreateDixlasePagesPageAction;
use Plugins\DixlasePages\App\Actions\Page\DeleteDixlasePagesPageAction;
use Plugins\DixlasePages\App\Actions\Page\ForceDeleteDixlasePagesPageAction;
use Plugins\DixlasePages\App\Actions\Page\RestoreDixlasePagesPageFromTrashAction;
use Plugins\DixlasePages\App\Actions\Page\UpdateDixlasePagesPageAction;
use Plugins\DixlasePages\App\Actions\Settings\UpdateDixlasePagesSettingsAction;
use Plugins\DixlasePages\App\Http\Requests\Admin\DixlasePagesStorePageRequest;
use Plugins\DixlasePages\App\Http\Requests\Admin\DixlasePagesUpdatePageRequest;
use Plugins\DixlasePages\App\Http\Requests\Admin\DixlasePagesUpdatePagesSettingsRequest;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Models\DixlasePagesPageSetting;
use Plugins\DixlasePages\App\Services\DixlasePagesPageContentService;
use Plugins\DixlasePages\App\Services\ScriptAuthoringPolicy;

class DixlasePagesAdminPagesController extends Controller
{
    use AdminInterfaceTrait;
    use AdminLoggedInTrait;
    use AuthorizesRequests;

    /**
     * PermissionRegistry resolves plugin roles.php via the PascalCase
     * directory basename, so this must be the directory name.
     */
    private const PLUGIN_SLUG = 'DixlasePages';

    protected DixlasePagesPageContentService $contentService;

    protected RevisionService $revisionService;

    protected MediaRepositoryInterface $mediaRepository;

    public function __construct(
        DixlasePagesPageContentService $contentService,
        RevisionService $revisionService,
        MediaRepositoryInterface $mediaRepository
    ) {
        $this->contentService = $contentService;
        $this->revisionService = $revisionService;
        $this->mediaRepository = $mediaRepository;
        $this->initialize();
        $this->initializeAfterLogin();
    }

    /**
     * Display preview from form data (display in new tab without saving)
     */
    public function preview(Request $request)
    {
        $page = new DixlasePagesPage();
        // An empty field may arrive as null rather than '' (core's
        // ConvertEmptyStringsToNull), and an input() default covers only a
        // missing key, so cast instead.
        $page->title = (string) $request->input('title');
        $page->slug = (string) $request->input('slug');
        $page->content = (string) $request->input('content');
        $page->custom_css = (string) $request->input('custom_css');
        $page->custom_js = (string) $request->input('custom_js');
        // Preview builds an unsaved page straight from request input, so
        // editor_type is attacker-controlled here in a way it never is for a
        // stored page. BLADE is downgraded rather than trusted: the front view
        // used to hand this content to Blade::render(), which turned a POST
        // body into executed PHP for anyone who could reach this endpoint.
        // Mirrors ContentPreviewService::renderFromSlug(), which applies the
        // same downgrade for exactly this reason.
        $requestedEditorType = ContentEditorType::tryFromSlug(
            (string) ($request->input('editor_type') ?: 'html')
        ) ?? ContentEditorType::HTML;

        $page->editor_type = $requestedEditorType === ContentEditorType::BLADE
            ? ContentEditorType::HTML
            : $requestedEditorType;
        // In preview, always treat as database and directly display the POSTed content
        $page->storage_type = ContentStorageType::DATABASE;
        $page->status = $request->input('status') ?: 'draft';
        $page->published_at = $request->input('published_at') ?: null;

        // Prepare view variables (because @php blocks are prohibited)
        $editorType = $page->editor_type->slug() ?? 'html';
        $content = $page->getContentByEditorType() ?? '';
        $hasCustomCss = ! empty($page->custom_css);
        $hasCustomJs = ! empty($page->custom_js);
        $customAssetVersion = time();

        return view('dixlase-pages::front.page', compact(
            'page',
            'editorType',
            'content',
            'hasCustomCss',
            'hasCustomJs',
            'customAssetVersion',
        ));
    }

    /**
     * Preview frame for iframe (display page with theme layout)
     *
     * Loaded in iframe within the admin panel page edit screen.
     * Uses the theme's layouts.preview to display with actual theme layout,
     * and updates content in real-time via postMessage.
     */
    public function previewFrame(DixlasePagesPage $page): \Illuminate\View\View
    {
        request()->attributes->set('csp_frame_ancestors_self', true);

        $themeSettings = $this->loadThemeSettingsForPreview();

        $previewService = app(ContentPreviewService::class);
        $rawContent = $page->getContentByEditorType() ?? '';
        $initialRenderedContent = $rawContent
            // renderFromSlug() downgrades Blade to HTML: a stored Blade body
            // (legacy row, restored revision) must never execute here, just
            // as preview() and previewRender() never execute it.
            ? $previewService->renderFromSlug($rawContent, $page->editor_type->slug(), false, false)
            : '';

        return view('dixlase-pages::admin.pages.preview-frame', [
            'page' => $page,
            'themeSettings' => $themeSettings,
            'initialRenderedContent' => $initialRenderedContent,
        ]);
    }

    /**
     * Empty preview frame for new page creation
     *
     * Used on the new page creation screen where page ID does not exist.
     * Displays an empty page structure with the theme's preview layout.
     */
    public function previewFrameNew(): \Illuminate\View\View
    {
        request()->attributes->set('csp_frame_ancestors_self', true);

        $themeSettings = $this->loadThemeSettingsForPreview();

        $page = new DixlasePagesPage();
        $page->title = '';

        return view('dixlase-pages::admin.pages.preview-frame', [
            'page' => $page,
            'themeSettings' => $themeSettings,
            'initialRenderedContent' => '',
        ]);
    }

    /**
     * Load theme settings for preview frame.
     *
     * @internal Documented exception (plugin.json _notes): reads core `themes`
     * and `theme_settings` tables directly because no ThemeRegistryInterface
     * contract exists yet. Slated for migration to a core contract in v0.2.
     * Keep all theme/theme_settings DB access centralized here so the v0.2
     * retrofit touches a single method.
     */
    protected function loadThemeSettingsForPreview(): object
    {
        try {
            $activeThemeId = DB::table('theme_settings')
                ->where('key', 'enabled_theme_id')
                ->value('value');

            if (! $activeThemeId) {
                return (object) [];
            }

            $theme = DB::table('themes')->find($activeThemeId);
            if (! $theme) {
                return (object) [];
            }

            $settingsTableName = 'thm_'.strtolower(str_replace('-', '_', $theme->slug)).'_settings';

            $settings = DB::table($settingsTableName)
                ->get()
                ->pluck('value', 'name');

            return (object) $settings->toArray();
        } catch (\Exception $e) {
            return (object) [];
        }
    }

    /**
     * Server-side preview rendering (for Markdown, Blade and GUI editors)
     *
     * Converts content to HTML for real-time preview within the iframe.
     * Markdown is rendered here rather than in the browser so the preview
     * escapes raw HTML exactly as the published page does.
     */
    public function previewRender(Request $request): JsonResponse
    {
        // An empty body may arrive as null; renderFromSlug() takes a string.
        $content = (string) $request->input('content');
        $editorTypeSlug = (string) ($request->input('editor_type') ?: 'html');

        $previewService = app(ContentPreviewService::class);
        $html = $previewService->renderFromSlug($content, $editorTypeSlug, false, false);

        return response()->json(['html' => $html]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $pages = DixlasePagesPage::query();

        // Search functionality (title, content, slug, description)
        if ($request->filled('search')) {
            $search = $request->get('search');
            $pages->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $pages->where('status', $request->get('status'));
        }

        // Get sort settings
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');

        // Allow only valid sort fields
        $allowedSorts = ['title', 'slug', 'status', 'created_at', 'updated_at', 'published_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }

        // Allow only valid sort order
        if (! in_array($order, ['asc', 'desc'])) {
            $order = 'desc';
        }

        // Pagination (with per-page count support)
        $perPage = $request->get('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;

        $pages = $pages->orderBy($sort, $order)
            ->paginate($perPage)
            ->withQueryString();

        // Get page directory settings from database
        $pagesDirectory = DixlasePagesPageSetting::getValue('route_slug', 'page');

        // Add URL to each page
        $pages->getCollection()->transform(function ($page) use ($pagesDirectory) {
            $page->page_url = config('app.url').'/'.$pagesDirectory.'/'.$page->slug;

            return $page;
        });

        return view('dixlase-pages::admin.pages.index', array_merge($this->viewParams, [
            'pages' => $pages,
            'currentSort' => $sort,
            'currentOrder' => $order,
        ]));
    }

    /**
     * Save SEO meta information via SEO plugin (optional dependency)
     *
     * @param  array<string, mixed>  $validated  Validated form data
     */
    private function saveSeoMeta(DixlasePagesPage $page, array $validated): void
    {
        if (! app()->has(SeoMetaProviderInterface::class)) {
            return;
        }

        $provider = app(SeoMetaProviderInterface::class);
        if (! $provider->isEnabledForPlugin('dixlase-pages')) {
            return;
        }

        $metaInput = $validated['seo_meta'] ?? [];
        $dto = new SeoMetaDTO(
            description: $metaInput['description'] ?? null,
            ogpMediaId: isset($metaInput['ogp_media_id']) ? (int) $metaInput['ogp_media_id'] : null,
        );
        $provider->saveMeta('dixlase-pages', (string) $page->id, $dto);
    }

    /**
     * Prepare data required for form display
     *
     * @param  DixlasePagesPage  $page  Page model
     * @param  string|null  $fileContents  Content loaded from file
     * @return array<string, mixed> Form data to pass to view
     */
    private function prepareFormData(DixlasePagesPage $page, ?string $fileContents = null, ?string $customCss = null, ?string $customJs = null): array
    {
        // Determine admin mode (Simple=0, Advanced=1)
        $isSimpleMode = (int) SiteSettings::get('admin_mode', 0) === 0;
        // Get content (empty for new page, from file or DB for existing page)
        $content = $fileContents ?? ($page->exists ? ($page->getContentByEditorType() ?? '') : '');

        // Custom CSS/JS
        $customCss = $customCss ?? ($page->custom_css ?? '');
        $customJs = $customJs ?? ($page->custom_js ?? '');

        // Page directory settings
        $pagesDirectory = DixlasePagesPageSetting::getValue('route_slug', 'page');
        $slugBaseUrl = config('app.url').'/'.$pagesDirectory.'/';

        // Storage save method options (for form-select)
        $storageOptions = [];
        $storageDescriptions = [];
        foreach (ContentStorageType::optionsWithDescription() as $value => $option) {
            $storageOptions[$value] = $option['label'];
            $storageDescriptions[$value] = $option['description'];
        }

        // Public permission check: whether the current member's role is equal to or higher than publish_min_role
        $publishMinRole = (int) DixlasePagesPageSetting::getValue('publish_min_role', MemberRole::EDITOR->value);
        $canPublish = $this->member && $this->member->role->value >= $publishMinRole;
        // The CSS tab for Markdown, for members who may write it.
        $markdownCss = ScriptAuthoringPolicy::canAuthorScripts($this->member);

        // Status options (for form-select)
        // Members without public permission can only use draft
        $statusOptions = [];
        foreach (ContentStatus::optionsWithDescription() as $value => $option) {
            if (! $canPublish && $value !== ContentStatus::DRAFT->slug()) {
                continue;
            }
            $statusOptions[$value] = $option['label'];
        }

        // Editor types available based on mode
        // Simple mode: GUI and Markdown only (unless editing a page with HTML/Blade)
        $simpleEditorSlugs = ['gui', 'markdown'];

        // For edit mode, include the page's current editor type even in simple mode (Strategy B)
        $currentEditorSlug = $page->exists ? $page->editor_type->slug() : null;
        $isAdvancedEditor = $currentEditorSlug && ! in_array($currentEditorSlug, $simpleEditorSlugs);

        // Editor slugs to exclude: Blade always; HTML in Simple mode (unless
        // the page already uses it) and for members who may not author
        // scripts (ScriptAuthoringPolicy -- the request enforces the same).
        $hideHtml = ($isSimpleMode && ! $isAdvancedEditor)
            || ! ScriptAuthoringPolicy::canAuthorScripts($this->member);
        $excludeSlugs = $hideHtml ? ['html', 'blade'] : ['blade'];

        // Editor type radio card options (for common components)
        $editorManager = app(\App\Services\Editor\EditorManager::class);
        $enabledByPlugin = $editorManager->getAvailableEditorTypes();
        $editorCardOptions = ContentEditorType::radioCardOptions(
            null,
            $excludeSlugs,
            $enabledByPlugin
        );

        // Status value including old() (for Alpine.js initialization)
        $statusValue = old('status', $page->status->slug());
        $publishedAtValue = old('published_at', $page->published_at ? $page->published_at->format('Y-m-d\TH:i') : '');

        // Display base path for file save
        $fileStorageBasePath = 'storage/app/private/'.$this->contentService->getBasePath();

        // Preview URL (separate tab preview)
        $previewUrl = Route::has('dixlase-pages::admin.pages.preview')
            ? route('dixlase-pages::admin.pages.preview')
            : '';

        // iframe preview frame URL (existing page when editing, empty frame when creating new)
        if ($page->exists && Route::has('dixlase-pages::admin.pages.preview-frame')) {
            $previewFrameUrl = route('dixlase-pages::admin.pages.preview-frame', $page);
        } elseif (Route::has('dixlase-pages::admin.pages.preview-frame-new')) {
            $previewFrameUrl = route('dixlase-pages::admin.pages.preview-frame-new');
        } else {
            $previewFrameUrl = '';
        }

        // Server-side rendering URL (for Blade/GUI editor)
        $previewRenderUrl = Route::has('dixlase-pages::admin.pages.preview-render')
            ? route('dixlase-pages::admin.pages.preview-render')
            : '';

        // Whether locale-prefixed URL routing is on. Drives the language
        // picker visibility on the create/edit form. Core answers it through
        // the LocaleUrlRouting contract, false when no extension binds it
        // (#83). A bound TranslationResolver alone is not a reliable signal,
        // since a provider may bind it with locale URLs turned off.
        $multilingualEnabled = LocaleHelper::localeUrlRoutingEnabled();

        // Language options (get labels according to locale from translation keys)
        $languageOptions = __('common.languages');

        // Current language value (from DB for existing pages, site default for new)
        $siteDefaultLocale = SiteSettings::get('locale', app()->getLocale());
        $langValue = old('lang', $page->lang ?? $siteDefaultLocale);

        // GUI editor info from plugin
        $guiEditorInfo = \App\Presenters\Admin\ContentEditorPresenter::guiEditorInfo();
        $guiEditorAssetHtml = $guiEditorInfo ? \App\Presenters\Admin\ContentEditorPresenter::editorAssetHtml($guiEditorInfo) : '';
        $hasGuiEditor = $guiEditorInfo !== null;

        // Parent page selector options. Build the breadcrumb label from
        // each candidate's ancestor chain. For an existing page, exclude
        // the page itself and its descendants (avoiding cycles) and any
        // candidate whose new depth would push this page's subtree past
        // DixlasePagesPage::MAX_DEPTH. For a new page the subtree is
        // empty, so the limit becomes parent.depth() <= MAX_DEPTH - 1.
        $excludeIds = $page->exists ? $page->subtreeIds() : [];
        $subtreeMax = $page->exists ? $page->subtreeMaxDepth() : 0;
        $parentOptions = [];
        // Map of [parent_id => "ancestor-slug/.../parent-slug"], handed to the
        // editor's Alpine state so the URL preview can prepend the parent path
        // (e.g. /page/philosophy/tesuto for a child of "philosophy").
        $parentPaths = [];
        foreach (DixlasePagesPage::query()->orderBy('id')->get() as $candidate) {
            if (in_array((int) $candidate->id, $excludeIds, true)) {
                continue;
            }
            if ($candidate->depth() + 1 + $subtreeMax > DixlasePagesPage::MAX_DEPTH) {
                continue;
            }
            $parentOptions[(int) $candidate->id] = implode(' / ', array_map(
                static fn ($p) => ($p->getTranslation('title') ?: $p->slug),
                $candidate->ancestorsAndSelf()
            ));
            $parentPaths[(int) $candidate->id] = implode('/', $candidate->pathSegments());
        }
        $parentValue = old('parent_id', $page->parent_id);

        // SEO meta information (only when SEO plugin is enabled and capability is declared)
        $seoMetaEnabled = false;
        $seoMeta = null;
        $seoOgpMedia = null;

        if (app()->has(SeoMetaProviderInterface::class)) {
            $provider = app(SeoMetaProviderInterface::class);
            if ($provider->isEnabledForPlugin('dixlase-pages')) {
                $seoMetaEnabled = true;
                if ($page->exists) {
                    $seoMeta = $provider->getMeta('dixlase-pages', (string) $page->id);
                    if ($seoMeta?->ogpMediaId) {
                        $seoOgpMedia = $this->mediaRepository->find($seoMeta->ogpMediaId);
                    }
                }
            }
        }

        return compact(
            'content',
            'customCss',
            'customJs',
            'slugBaseUrl',
            'storageOptions',
            'storageDescriptions',
            'statusOptions',
            'editorCardOptions',
            'statusValue',
            'publishedAtValue',
            'fileStorageBasePath',
            'previewUrl',
            'previewFrameUrl',
            'previewRenderUrl',
            'languageOptions',
            'langValue',
            'multilingualEnabled',
            'isSimpleMode',
            'isAdvancedEditor',
            'canPublish',
            'markdownCss',
            'guiEditorInfo',
            'guiEditorAssetHtml',
            'hasGuiEditor',
            'seoMetaEnabled',
            'seoMeta',
            'seoOgpMedia',
            'parentOptions',
            'parentValue',
            'parentPaths',
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $page = new DixlasePagesPage();

        // Determine admin mode (Simple=0, Advanced=1)
        $isSimpleMode = (int) SiteSettings::get('admin_mode', 0) === 0;

        // Apply default values from settings (int-backed enums need conversion from slug)
        $page->status = DixlasePagesPageSetting::getValue('default_status', 'draft');

        if ($isSimpleMode) {
            // Simple mode: force database storage, use default editor (GUI/Markdown only)
            $page->storage_type = ContentStorageType::DATABASE;
            $defaultEditor = DixlasePagesPageSetting::getValue('default_editor_type', 'markdown');
            $simpleSlugs = ['gui', 'markdown'];
            // If the saved default is not GUI/MD, fall back to markdown
            $page->editor_type = in_array($defaultEditor, $simpleSlugs)
                ? (ContentEditorType::tryFromSlug($defaultEditor) ?? ContentEditorType::MARKDOWN)
                : ContentEditorType::MARKDOWN;
        } else {
            $page->editor_type = ContentEditorType::tryFromSlug(
                DixlasePagesPageSetting::getValue('default_editor_type', 'html')
            ) ?? ContentEditorType::HTML;
            $page->storage_type = ContentStorageType::tryFromSlug(
                DixlasePagesPageSetting::getValue('default_storage_type', 'database')
            ) ?? ContentStorageType::DATABASE;
        }

        // A default the member may not use (HTML for an editor) falls back
        // to Markdown instead of pre-selecting an option the form hides.
        if (! in_array($page->editor_type->slug(), ScriptAuthoringPolicy::creatableEditorSlugs($this->member), true)) {
            $page->editor_type = ContentEditorType::MARKDOWN;
        }

        $formData = $this->prepareFormData($page);

        return view('dixlase-pages::admin.pages.create', array_merge(
            $this->viewParams,
            compact('page'),
            $formData,
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DixlasePagesStorePageRequest $request)
    {
        $validated = $request->validated();

        /** @var DixlasePagesPage $page */
        $page = (new CreateDixlasePagesPageAction($this->contentService, $this->revisionService))
            ->execute($this->actor(), $validated)
            ->model;

        // Save SEO meta (only when SEO plugin is enabled)
        $this->saveSeoMeta($page, $validated);

        return redirect()
            ->route('dixlase-pages::admin.pages.edit', $page)
            ->with('success', __('dixlase-pages::admin/pages/create.success'));
    }

    /**
     * Display the specified resource.
     *
     * There is no dedicated detail view -- clicking a page from the index
     * always leads to the editor -- so a direct hit on /admin/pages/{page}
     * is sent to the same place rather than 500-ing on a missing view.
     */
    public function show(DixlasePagesPage $page)
    {
        return redirect()->route('dixlase-pages::admin.pages.edit', ['page' => $page->id]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DixlasePagesPage $page)
    {
        // HTML (and legacy Blade) pages are script-capable; only members who
        // may author scripts may open them for editing (the update request
        // refuses the save as well).
        abort_unless(ScriptAuthoringPolicy::canEditEditorType($this->member, $page->editor_type), 403);

        // For file storage, load content from file
        $fileContents = null;
        $customCss = null;
        $customJs = null;
        $locale = app()->getLocale();

        // A file-stored page edits its files: the working copy, which may
        // hold changes that are not published yet.
        $publisher = app(FileContentPublisher::class);
        if ($page->storage_type === ContentStorageType::FILE) {
            $fileContents = $this->contentService->loadFromFile(
                $page->slug,
                $locale,
                $page->editor_type->slug()
            );
            $files = $publisher->readFiles($page);
            $customCss = $files['custom_css'] ?? null;
            $customJs = $files['custom_js'] ?? null;
        }

        $formData = $this->prepareFormData($page, $fileContents, $customCss, $customJs);

        // Changes made to the files outside the admin that are not published
        $publisher->check($page);
        $diffPresenter = new RevisionDiffPresenter();
        $fileChanges = [];
        foreach ($publisher->pendingChanges($page) as $part => $change) {
            $fileChanges[$part] = $diffPresenter->buildSideBySide($change['published'], $change['file']);
        }

        return view('dixlase-pages::admin.pages.edit', array_merge(
            $this->viewParams,
            compact('page', 'fileChanges'),
            ['canPublishFile' => $this->canPublishFile($page)],
            $formData,
        ));
    }

    /**
     * Publish the changes made to a file-stored page's files outside the
     * admin
     */
    public function publishFile(DixlasePagesPage $page): RedirectResponse
    {
        abort_unless($this->canPublishFile($page), 403);

        $result = (new PublishFileContentAction($page, null, app(FileContentPublisher::class)))
            ->execute($this->actor(), []);

        return redirect()
            ->route('dixlase-pages::admin.pages.edit', $page)
            ->with($result->success ? 'success' : 'error', $result->success
                ? __('dixlase-pages::admin/pages/edit.publish_file_success')
                : $result->message);
    }

    /**
     * Whether the signed-in member may publish a page's file changes
     *
     * The same members who may save the page: script-capable pages need
     * script authoring rights, and a page that is not a draft needs the
     * publish_min_role setting, since publishing changes what visitors see.
     */
    private function canPublishFile(DixlasePagesPage $page): bool
    {
        if (! ScriptAuthoringPolicy::canEditEditorType($this->member, $page->editor_type)) {
            return false;
        }

        if ($page->status === ContentStatus::DRAFT) {
            return true;
        }

        $publishMinRole = (int) DixlasePagesPageSetting::getValue('publish_min_role', MemberRole::EDITOR->value);

        return $this->member !== null && $this->member->role->value >= $publishMinRole;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DixlasePagesUpdatePageRequest $request, DixlasePagesPage $page)
    {
        $validated = $request->validated();

        (new UpdateDixlasePagesPageAction($page, $this->contentService, $this->revisionService))
            ->execute($this->actor(), $validated);

        // Save SEO meta (only when SEO plugin is enabled)
        $this->saveSeoMeta($page, $validated);

        return redirect()
            ->route('dixlase-pages::admin.pages.edit', $page)
            ->with('success', __('dixlase-pages::admin/pages/edit.success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DixlasePagesPage $page)
    {
        // Soft delete to move to trash (files and SEO meta are deleted on forceDelete)
        (new DeleteDixlasePagesPageAction($page))->execute($this->actor(), []);

        return redirect()
            ->route('dixlase-pages::admin.pages.index')
            ->with('success', __('dixlase-pages::admin/pages/index.delete_success'));
    }

    /**
     * Display trash (soft-deleted pages) list
     */
    public function trash(Request $request)
    {
        $pages = DixlasePagesPage::onlyTrashed();

        if ($request->filled('search')) {
            $search = $request->get('search');
            $pages->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $sort = $request->get('sort', 'deleted_at');
        $order = $request->get('order', 'desc');
        $allowedSorts = ['title', 'slug', 'deleted_at', 'created_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'deleted_at';
        }
        if (! in_array($order, ['asc', 'desc'])) {
            $order = 'desc';
        }

        $perPage = $request->get('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;

        $pages = $pages->orderBy($sort, $order)
            ->paginate($perPage)
            ->withQueryString();

        return view('dixlase-pages::admin.pages.trash', array_merge($this->viewParams, [
            'pages' => $pages,
            'currentSort' => $sort,
            'currentOrder' => $order,
            // An editor may open this screen to restore their own pages, but
            // emptying the trash and deleting outright require ADMIN (see
            // config/admin/roles.php). Without this the controls render for
            // everyone and answer 403, which reads as a broken screen rather
            // than a permission boundary.
            'canDeletePermanently' => AdminHelper::canEditPluginMenu('DixlasePages', 'pages.trash.empty'),
        ]));
    }

    /**
     * Restore a page from trash
     */
    public function restore(int $id)
    {
        $page = DixlasePagesPage::onlyTrashed()->findOrFail($id);

        // Check for slug collision (possibility that a new page with the same slug was created while deleted)
        $exists = DixlasePagesPage::where('slug', $page->slug)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return redirect()
                ->route('dixlase-pages::admin.pages.trash')
                ->with('error', __('dixlase-pages::admin/pages/trash.restore_slug_conflict', ['slug' => $page->slug]));
        }

        (new RestoreDixlasePagesPageFromTrashAction($page))->execute($this->actor(), []);

        return redirect()
            ->route('dixlase-pages::admin.pages.trash')
            ->with('success', __('dixlase-pages::admin/pages/trash.restore_success'));
    }

    /**
     * Permanently delete a page in trash (cannot be undone)
     */
    public function forceDestroy(int $id)
    {
        $page = DixlasePagesPage::onlyTrashed()->findOrFail($id);
        (new ForceDeleteDixlasePagesPageAction($page))->execute($this->actor(), []);

        return redirect()
            ->route('dixlase-pages::admin.pages.trash')
            ->with('success', __('dixlase-pages::admin/pages/trash.force_delete_success'));
    }

    /**
     * Empty trash (permanently delete all pages)
     */
    public function emptyTrash()
    {
        $count = 0;
        $actor = $this->actor();
        DixlasePagesPage::onlyTrashed()->each(function ($page) use (&$count, $actor) {
            // One audit entry per page, so each deletion can be traced
            (new ForceDeleteDixlasePagesPageAction($page))->execute($actor, []);
            $count++;
        });

        return redirect()
            ->route('dixlase-pages::admin.pages.trash')
            ->with('success', __('dixlase-pages::admin/pages/trash.empty_success', ['count' => $count]));
    }

    /**
     * Show the settings page.
     */
    public function settings()
    {
        $this->authorizeView('pages.settings');

        // Disable the save button for members who may view but not edit this
        // page. Core's <x-admin.save-button> reads the shared `menuEditable`
        // flag (normally set by CheckMenuAccess middleware, which does not run
        // on plugin admin routes), so share it here from the plugin permission.
        View::share('menuEditable', AdminHelper::canEditPluginMenu(self::PLUGIN_SLUG, 'pages.settings'));

        // Determine admin mode (Simple=0, Advanced=1)
        $isSimpleMode = (int) SiteSettings::get('admin_mode', 0) === 0;

        // Get settings data
        $settings = DixlasePagesPageSetting::pluck('value', 'name')->toArray();

        // Radio card options for default status
        $statusIcons = [
            'draft' => 'fas fa-pencil-alt',
            'published' => 'fas fa-eye',
            'scheduled' => 'fas fa-calendar-alt',
        ];
        $statusCardOptions = [];
        foreach ([ContentStatus::DRAFT, ContentStatus::PUBLISHED, ContentStatus::SCHEDULED] as $status) {
            $slug = $status->slug();
            $statusCardOptions[] = [
                'value' => $slug,
                'label' => $status->label(),
                'description' => $status->description(),
                'icon' => $statusIcons[$slug],
                'color' => $status->cssClass(),
            ];
        }

        // Radio card options for editor type (Blade is currently disabled)
        $editorIcons = [
            'gui' => 'fas fa-magic',
            'markdown' => 'fab fa-markdown',
            'html' => 'fas fa-code',
        ];
        $editorColors = [
            'gui' => 'purple',
            'markdown' => 'blue',
            'html' => 'orange',
        ];
        // Simple mode: only GUI and Markdown editors available
        $simpleModeEditorSlugs = ['gui', 'markdown'];

        $editorTypeCardOptions = [];
        foreach (ContentEditorType::cases() as $type) {
            // Blade editor is disabled in the current version
            if ($type === ContentEditorType::BLADE) {
                continue;
            }
            $slug = $type->slug();
            // Simple mode: skip HTML editor
            if ($isSimpleMode && ! in_array($slug, $simpleModeEditorSlugs)) {
                continue;
            }
            $editorTypeCardOptions[] = [
                'value' => $slug,
                'label' => __($type->translationKey()),
                'description' => __($type->descriptionKey()),
                'icon' => $editorIcons[$slug] ?? 'fas fa-file',
                'color' => $editorColors[$slug] ?? 'gray',
            ];
        }

        // Radio card options for storage type
        $storageIcons = [
            'database' => 'fas fa-database',
            'file' => 'fas fa-file-code',
        ];
        $storageColors = [
            'database' => 'blue',
            'file' => 'green',
        ];
        $storageTypeCardOptions = [];
        foreach (ContentStorageType::cases() as $type) {
            $slug = $type->slug();
            $storageTypeCardOptions[] = [
                'value' => $slug,
                'label' => __($type->translationKey()),
                'description' => __($type->descriptionKey()),
                'icon' => $storageIcons[$slug],
                'color' => $storageColors[$slug],
            ];
        }

        // Base URL for displaying page directory URLs
        $siteUrl = config('app.url');

        // Data for public permission role slider
        // Only roles with editor permission or higher can be selected (guest/receptionist/contributor excluded)
        $publishRoleOptions = [];
        foreach (MemberRole::cases() as $role) {
            if ($role === MemberRole::GUEST || $role === MemberRole::SUPER_ADMIN) {
                continue;
            }
            $publishRoleOptions[$role->value] = $role->label();
        }
        ksort($publishRoleOptions);

        $publishRoleValues = [];
        $publishRoleLabels = [];
        $index = 0;
        foreach ($publishRoleOptions as $value => $label) {
            $publishRoleValues[$index] = $value;
            $publishRoleLabels[$index] = $label;
            $index++;
        }
        $publishValueToIndex = array_flip($publishRoleValues);
        $publishMaxIndex = count($publishRoleValues) - 1;

        // Current publish_min_role settings value
        $currentPublishMinRole = (int) ($settings['publish_min_role'] ?? MemberRole::EDITOR->value);
        $publishRoleIndex = $publishValueToIndex[$currentPublishMinRole] ?? 0;

        $this->viewParams['publishMode'] = ContentPublishMode::fromSetting($settings[DixlasePagesPage::PUBLISH_MODE_SETTING] ?? null)->value;
        $this->viewParams['publishModeOptions'] = ContentPublishMode::options();
        $this->viewParams['fileSaveModeAvailable'] = FileContentPublisher::fileSaveModeAvailable();
        $this->viewParams['settings'] = $settings;
        $this->viewParams['statusCardOptions'] = $statusCardOptions;
        $this->viewParams['editorTypeCardOptions'] = $editorTypeCardOptions;
        $this->viewParams['storageTypeCardOptions'] = $storageTypeCardOptions;
        $this->viewParams['siteUrl'] = $siteUrl;
        $this->viewParams['isSimpleMode'] = $isSimpleMode;
        $this->viewParams['publishRoleValues'] = $publishRoleValues;
        $this->viewParams['publishRoleLabels'] = $publishRoleLabels;
        $this->viewParams['publishMaxIndex'] = $publishMaxIndex;
        $this->viewParams['publishRoleIndex'] = $publishRoleIndex;

        return view('dixlase-pages::admin.pages.settings', $this->viewParams);
    }

    /**
     * Update the settings.
     */
    public function updateSettings(DixlasePagesUpdatePagesSettingsRequest $request)
    {
        $this->authorizeEdit('pages.settings');

        (new UpdateDixlasePagesSettingsAction())->execute($this->actor(), $request->validated());

        return redirect()
            ->route('dixlase-pages::admin.pages.settings')
            ->with('success', __('dixlase-pages::admin/pages/settings.success'));
    }

    /**
     * The signed-in member as the actor of an audited page action.
     */
    private function actor(): MemberActor
    {
        return new MemberActor(AdminHelper::getMember());
    }

    /**
     * Abort with 403 unless the current member can VIEW the given menu.
     * SUPER_ADMIN bypasses; core role_permission_overrides and the plugin
     * defaults in config/admin/roles.php are honoured via AdminHelper.
     */
    private function authorizeView(string $menuKey): void
    {
        if (! AdminHelper::canViewPluginMenu(self::PLUGIN_SLUG, $menuKey)) {
            abort(403, __('http/middleware/check_menu_access.no_access_permission'));
        }
    }

    /**
     * Abort with 403 unless the current member can EDIT the given menu.
     */
    private function authorizeEdit(string $menuKey): void
    {
        if (! AdminHelper::canEditPluginMenu(self::PLUGIN_SLUG, $menuKey)) {
            abort(403, __('http/middleware/check_menu_edit.no_edit_permission'));
        }
    }
}
