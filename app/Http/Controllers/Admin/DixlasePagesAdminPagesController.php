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

namespace Plugins\DixlasePages\App\Http\Controllers\Admin;

use App\Contracts\PluginIntegration\SeoMetaProviderInterface;
use App\Contracts\Repositories\MediaRepositoryInterface;
use App\DTO\PluginIntegration\SeoMetaDTO;
use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Enums\MemberRole;
use App\Facades\SiteSettings;
use App\Helpers\AdminHelper;
use App\Services\ContentPreviewService;
use App\Services\RevisionService;
use App\Traits\AdminInterfaceTrait;
use App\Traits\AdminLoggedInTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Plugins\DixlasePages\App\Http\Requests\Admin\DixlasePagesStorePageRequest;
use Plugins\DixlasePages\App\Http\Requests\Admin\DixlasePagesUpdatePageRequest;
use Plugins\DixlasePages\App\Http\Requests\Admin\DixlasePagesUpdatePagesSettingsRequest;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Models\DixlasePagesPageSetting;
use Plugins\DixlasePages\App\Services\DixlasePagesPageContentService;

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
        $page->title = $request->input('title', '');
        $page->slug = $request->input('slug', '');
        $page->content = $request->input('content', '');
        $page->custom_css = $request->input('custom_css', '');
        $page->custom_js = $request->input('custom_js', '');
        $page->editor_type = ContentEditorType::tryFromSlug(
            $request->input('editor_type', 'html')
        ) ?? ContentEditorType::HTML;
        // In preview, always treat as database and directly display the POSTed content
        $page->storage_type = ContentStorageType::DATABASE;
        $page->status = $request->input('status', 'draft');
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
            ? $previewService->render($rawContent, $page->editor_type)
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
     * Server-side preview rendering (for Blade/GUI editors)
     *
     * Converts content from editor types (Blade, GUI) that cannot be rendered client-side
     * to HTML for real-time preview within iframe.
     */
    public function previewRender(Request $request): JsonResponse
    {
        $content = $request->input('content', '');
        $editorTypeSlug = $request->input('editor_type', 'html');

        $previewService = app(ContentPreviewService::class);
        $html = $previewService->renderFromSlug($content, $editorTypeSlug);

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

        // Editor slugs to exclude in Simple mode
        $excludeSlugs = $isSimpleMode && ! $isAdvancedEditor ? ['html', 'blade'] : ['blade'];

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

        // Whether the multilingual plugin's locale URL routing is on. Drives
        // the language picker visibility on the create/edit form. We check
        // the same config flag the multilingual plugin itself gates its
        // runtime locale wiring on; the resolver binding alone is not a
        // reliable signal because the multilingual plugin always registers
        // it (even when the operator has the master toggle off).
        $multilingualEnabled = (bool) config('dixlase_multilingual.locale_url_routing_enabled', false);

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

        $storageTypeSlug = $validated['storage_type'];
        $editorTypeSlug = $validated['editor_type'];
        $content = $validated['content'] ?? '';
        $customCss = $validated['custom_css'] ?? '';
        $customJs = $validated['custom_js'] ?? '';
        $lang = $validated['lang'] ?? app()->getLocale();

        // For file save, also save to file
        if ($storageTypeSlug === 'file') {
            $this->contentService->saveToFile(
                $validated['slug'],
                $lang,
                $editorTypeSlug,
                $content
            );

            // Save CSS/JS files as well
            if (! empty($customCss)) {
                $this->contentService->saveCssToFile($validated['slug'], $lang, $customCss);
            }
            if (! empty($customJs)) {
                $this->contentService->saveJsToFile($validated['slug'], $lang, $customJs);
            }
        }

        // Convert from slug to enum instance (int-backed enums need conversion from slug)
        $storageType = ContentStorageType::tryFromSlug($storageTypeSlug) ?? ContentStorageType::DATABASE;
        $editorType = ContentEditorType::tryFromSlug($editorTypeSlug) ?? ContentEditorType::HTML;

        // Create page (always save content to DB as well = backup)
        $page = DixlasePagesPage::create([
            'parent_id' => $validated['parent_id'] ?? null,
            'slug' => $validated['slug'],
            'lang' => $lang,
            'title' => $validated['title'] ?? null,
            'storage_type' => $storageType,
            'editor_type' => $editorType,
            'status' => $validated['status'],
            'published_at' => $validated['published_at'] ?? null,
            'content' => $content,
            'custom_css' => $customCss,
            'custom_js' => $customJs,
        ]);

        // Record revision (manual because it's an explicit save by user)
        $this->revisionService->record(
            $page->fresh() ?? $page,
            type: RevisionService::TYPE_MANUAL,
            userId: $this->member?->id,
        );

        // Save SEO meta (only when SEO plugin is enabled)
        $this->saveSeoMeta($page, $validated);

        return redirect()
            ->route('dixlase-pages::admin.pages.edit', $page)
            ->with('success', __('dixlase-pages::admin/pages/create.success'));
    }

    /**
     * Display the specified resource.
     */
    public function show(DixlasePagesPage $page)
    {
        return view('dixlase-pages::admin.pages.show', array_merge($this->viewParams, compact('page')));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DixlasePagesPage $page)
    {
        // For file storage, load content from file
        $fileContents = null;
        $customCss = null;
        $customJs = null;
        $locale = app()->getLocale();

        if ($page->storage_type === ContentStorageType::FILE) {
            $fileContents = $this->contentService->loadFromFile(
                $page->slug,
                $locale,
                $page->editor_type->slug()
            );
            $customCss = $this->contentService->getCssContent($page, $locale);
            $customJs = $this->contentService->getJsContent($page, $locale);
        }

        $formData = $this->prepareFormData($page, $fileContents, $customCss, $customJs);

        return view('dixlase-pages::admin.pages.edit', array_merge(
            $this->viewParams,
            compact('page'),
            $formData,
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DixlasePagesUpdatePageRequest $request, DixlasePagesPage $page)
    {
        $validated = $request->validated();

        // editor_type maintains the existing model value (cannot be changed during editing)
        $editorTypeSlug = $page->editor_type->slug();
        $storageTypeSlug = $validated['storage_type'];
        $newStorageType = ContentStorageType::tryFromSlug($storageTypeSlug) ?? ContentStorageType::DATABASE;

        $oldSlug = $page->slug;
        $oldStorageType = $page->storage_type;
        $locale = app()->getLocale();

        // If slug is changed, rename the file
        if ($oldStorageType === ContentStorageType::FILE && $oldSlug !== $validated['slug']) {
            $this->contentService->renameFile($oldSlug, $validated['slug'], $editorTypeSlug, $locale);
            // Rename CSS/JS files as well
            $this->contentService->renameAssetFiles($oldSlug, $validated['slug'], $locale);
        }

        $content = $validated['content'] ?? '';
        $customCss = $validated['custom_css'] ?? '';
        $customJs = $validated['custom_js'] ?? '';

        // Handle storage method changes
        if ($oldStorageType !== $newStorageType) {
            if ($oldStorageType === ContentStorageType::FILE && $newStorageType === ContentStorageType::DATABASE) {
                // File→DB: Delete file (no need to load since DB always has backup)
                $this->contentService->deleteFile($validated['slug'], $locale, $editorTypeSlug);
                $this->contentService->deleteCssFile($validated['slug'], $locale);
                $this->contentService->deleteJsFile($validated['slug'], $locale);
            }
        }

        // For file save, also save to file
        if ($newStorageType === ContentStorageType::FILE) {
            $this->contentService->saveToFile(
                $validated['slug'],
                $locale,
                $editorTypeSlug,
                $content
            );

            // Save CSS/JS files as well
            if (! empty($customCss)) {
                $this->contentService->saveCssToFile($validated['slug'], $locale, $customCss);
            } else {
                $this->contentService->deleteCssFile($validated['slug'], $locale);
            }
            if (! empty($customJs)) {
                $this->contentService->saveJsToFile($validated['slug'], $locale, $customJs);
            } else {
                $this->contentService->deleteJsFile($validated['slug'], $locale);
            }
        }

        // Update page (always save content to DB as backup)
        $page->update([
            'parent_id' => $validated['parent_id'] ?? null,
            'slug' => $validated['slug'],
            'lang' => $validated['lang'] ?? app()->getLocale(),
            'title' => $validated['title'] ?? null,
            'storage_type' => $newStorageType,
            'status' => $validated['status'],
            'published_at' => $validated['published_at'] ?? null,
            'content' => $content,
            'custom_css' => $customCss,
            'custom_js' => $customJs,
        ]);

        // Keep any existing multilingual translation overlay in sync with the
        // column write. Without this, an overlay row that already exists for
        // the current locale (e.g. one the operator authored via the
        // translation manager earlier) shadows the column on display, so
        // edits made here would silently "revert" the moment the page is
        // re-rendered. We only touch overlays that already exist — new ones
        // are still owned by the translation manager, so a fresh page won't
        // suddenly grow overlay rows just because someone saved it once.
        if (\Illuminate\Support\Facades\App::bound(\App\Contracts\TranslationResolver::class)) {
            $resolver = app(\App\Contracts\TranslationResolver::class);
            $currentLocale = app()->getLocale();
            $titleValue = $validated['title'] ?? '';
            foreach ([['title', $titleValue], ['content', $content]] as [$field, $value]) {
                if ($resolver->exists($page, $field, $currentLocale)) {
                    $resolver->store($page, $field, $value, $currentLocale);
                }
            }
        }

        // Record revision (manual because it's an explicit save by user)
        // Skipped if there are no changes from the previous revision
        $this->revisionService->record(
            $page->fresh() ?? $page,
            type: RevisionService::TYPE_MANUAL,
            userId: $this->member?->id,
        );

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
        $page->delete();

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

        $page->restore();

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
        $page->forceDelete();

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
        DixlasePagesPage::onlyTrashed()->each(function ($page) use (&$count) {
            $page->forceDelete();
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

        $validated = $request->validated();

        // Save settings to database
        DixlasePagesPageSetting::setMany($validated);

        return redirect()
            ->route('dixlase-pages::admin.pages.settings')
            ->with('success', __('dixlase-pages::admin/pages/settings.success'));
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

    /**
     * Get file content for a specific editor type (API endpoint).
     */
    public function getFileContent(DixlasePagesPage $page, string $editorType)
    {
        // Return empty if not file storage
        if ($page->storage_type !== ContentStorageType::FILE) {
            return response()->json(['content' => '']);
        }

        $content = $this->contentService->loadFromFile(
            $page->slug,
            app()->getLocale(),
            $editorType
        );

        return response()->json(['content' => $content ?? '']);
    }

    /**
     * Get content for a specific storage type and editor type (API endpoint).
     * Used when switching storage type or editor type.
     */
    public function getContent(DixlasePagesPage $page, string $storageType, string $editorType)
    {
        $content = '';

        if ($storageType === 'file') {
            // Load content from file
            $content = $this->contentService->loadFromFile(
                $page->slug,
                app()->getLocale(),
                $editorType
            ) ?? '';
        } else {
            // Load content column from DB
            $content = $page->content ?? '';
        }

        return response()->json(['content' => $content]);
    }
}
