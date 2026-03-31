<?php

/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
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

use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Enums\MemberRole;
use App\Models\BaseSetting;
use App\Traits\AdminInterfaceTrait;
use App\Traits\AdminLoggedInTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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

    protected DixlasePagesPageContentService $contentService;

    public function __construct(DixlasePagesPageContentService $contentService)
    {
        $this->contentService = $contentService;
        $this->initialize();
        $this->initializeAfterLogin();
    }

    /**
     * フォームデータからプレビュー表示する（保存せずに新しいタブで表示）
     */
    public function preview(Request $request)
    {
        $page = new DixlasePagesPage;
        $page->title = $request->input('title', '');
        $page->slug = $request->input('slug', '');
        $page->content = $request->input('content', '');
        $page->custom_css = $request->input('custom_css', '');
        $page->custom_js = $request->input('custom_js', '');
        $page->editor_type = ContentEditorType::tryFromSlug(
            $request->input('editor_type', 'html')
        ) ?? ContentEditorType::HTML;
        // プレビューでは常にdatabaseとして扱い、POSTされたcontentを直接表示する
        $page->storage_type = ContentStorageType::DATABASE;
        $page->status = $request->input('status', 'draft');
        $page->published_at = $request->input('published_at') ?: null;

        // ビュー変数を準備（@phpブロック禁止のため）
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
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $pages = DixlasePagesPage::query();

        // 検索機能（タイトル、コンテンツ、スラッグ、説明文）
        if ($request->filled('search')) {
            $search = $request->get('search');
            $pages->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // ステータスフィルター
        if ($request->filled('status')) {
            $pages->where('status', $request->get('status'));
        }

        // ソート設定を取得
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');

        // 有効なソートフィールドのみ許可
        $allowedSorts = ['title', 'slug', 'status', 'created_at', 'updated_at', 'published_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }

        // 有効なソート順序のみ許可
        if (! in_array($order, ['asc', 'desc'])) {
            $order = 'desc';
        }

        // ページネーション（件数指定対応）
        $perPage = $request->get('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;

        $pages = $pages->orderBy($sort, $order)
            ->paginate($perPage)
            ->withQueryString();

        // ページディレクトリ設定をデータベースから取得
        $pagesDirectory = DixlasePagesPageSetting::getValue('route_slug', 'pages');

        // 各ページにURLを追加
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
     * フォーム表示に必要なデータを準備する
     *
     * @param  DixlasePagesPage  $page  ページモデル
     * @param  string|null  $fileContents  ファイルから読み込んだコンテンツ
     * @return array<string, mixed> ビューに渡すフォームデータ
     */
    private function prepareFormData(DixlasePagesPage $page, ?string $fileContents = null, ?string $customCss = null, ?string $customJs = null): array
    {
        // Determine admin mode (Simple=0, Advanced=1)
        $isSimpleMode = (int) BaseSetting::getValue('admin_mode', 0) === 0;
        // コンテンツ取得（新規ページの場合は空、既存ページはファイルまたはDBから）
        $content = $fileContents ?? ($page->exists ? ($page->getContentByEditorType() ?? '') : '');

        // カスタムCSS/JS
        $customCss = $customCss ?? ($page->custom_css ?? '');
        $customJs = $customJs ?? ($page->custom_js ?? '');

        // ページディレクトリ設定
        $pagesDirectory = DixlasePagesPageSetting::getValue('route_slug', 'pages');
        $slugBaseUrl = config('app.url').'/'.$pagesDirectory.'/';

        // ストレージ保存方法オプション（form-select用）
        $storageOptions = [];
        $storageDescriptions = [];
        foreach (ContentStorageType::optionsWithDescription() as $value => $option) {
            $storageOptions[$value] = $option['label'];
            $storageDescriptions[$value] = $option['description'];
        }

        // 公開権限チェック: 現在のメンバーのロールが publish_min_role 以上か
        $publishMinRole = (int) DixlasePagesPageSetting::getValue('publish_min_role', MemberRole::EDITOR->value);
        $canPublish = $this->member && $this->member->role->value >= $publishMinRole;

        // ステータスオプション（form-select用）
        // 公開権限がないメンバーは下書きのみ
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
        $allEditorSlugs = ['gui', 'markdown', 'html'];

        // For edit mode, include the page's current editor type even in simple mode (Strategy B)
        $currentEditorSlug = $page->exists ? $page->editor_type->slug() : null;
        $isAdvancedEditor = $currentEditorSlug && ! in_array($currentEditorSlug, $simpleEditorSlugs);

        $activeSlugs = $isSimpleMode && ! $isAdvancedEditor ? $simpleEditorSlugs : $allEditorSlugs;

        // Editor translation keys (for Alpine.js $t())
        $editorTranslations = [];
        foreach ($activeSlugs as $slug) {
            $editorTranslations["common.content_editor.{$slug}"] = __("common.content_editor.{$slug}");
            $editorTranslations["common.content_editor.{$slug}_description"] = __("common.content_editor.{$slug}_description");
        }

        // Editor type icon/color maps
        $editorIconsMap = [
            'gui' => 'fas fa-magic',
            'markdown' => 'fab fa-markdown',
            'html' => 'fas fa-code',
        ];
        $editorColorsMap = [
            'gui' => 'purple',
            'markdown' => 'blue',
            'html' => 'orange',
        ];
        $editorIcons = array_intersect_key($editorIconsMap, array_flip($activeSlugs));
        $editorColors = array_intersect_key($editorColorsMap, array_flip($activeSlugs));

        // old()込みのステータス値（Alpine.js初期化用）
        $statusValue = old('status', $page->status->slug());
        $publishedAtValue = old('published_at', $page->published_at ? $page->published_at->format('Y-m-d\TH:i') : '');

        // ファイル保存時の表示用ベースパス
        $fileStorageBasePath = 'storage/app/private/'.$this->contentService->getBasePath();

        // プレビューURL
        $previewUrl = Route::has('dixlase-pages::admin.pages.preview')
            ? route('dixlase-pages::admin.pages.preview')
            : '';

        // 言語オプション（翻訳キーからロケールに応じたラベルを取得）
        $languageOptions = __('common.languages');

        // 現在の言語値（既存ページはDBから、新規はアプリ言語）
        $langValue = old('lang', $page->lang ?? app()->getLocale());

        // GUI editor info from plugin
        $guiEditorInfo = \App\Presenters\Admin\ContentEditorPresenter::guiEditorInfo();
        $guiEditorAssetHtml = $guiEditorInfo ? \App\Presenters\Admin\ContentEditorPresenter::editorAssetHtml($guiEditorInfo) : '';
        $hasGuiEditor = $guiEditorInfo !== null;

        return compact(
            'content',
            'customCss',
            'customJs',
            'slugBaseUrl',
            'storageOptions',
            'storageDescriptions',
            'statusOptions',
            'editorTranslations',
            'editorIcons',
            'editorColors',
            'statusValue',
            'publishedAtValue',
            'fileStorageBasePath',
            'previewUrl',
            'languageOptions',
            'langValue',
            'isSimpleMode',
            'isAdvancedEditor',
            'canPublish',
            'guiEditorInfo',
            'guiEditorAssetHtml',
            'hasGuiEditor',
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $page = new DixlasePagesPage;

        // Determine admin mode (Simple=0, Advanced=1)
        $isSimpleMode = (int) BaseSetting::getValue('admin_mode', 0) === 0;

        // 設定のデフォルト値を適用（int-backed enumにはslugから変換が必要）
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

        // ファイル保存の場合はファイルにも保存
        if ($storageTypeSlug === 'file') {
            $this->contentService->saveToFile(
                $validated['slug'],
                $lang,
                $editorTypeSlug,
                $content
            );

            // CSS/JSファイルも保存
            if (! empty($customCss)) {
                $this->contentService->saveCssToFile($validated['slug'], $lang, $customCss);
            }
            if (! empty($customJs)) {
                $this->contentService->saveJsToFile($validated['slug'], $lang, $customJs);
            }
        }

        // スラッグからenumインスタンスに変換（int-backed enumはslugから変換が必要）
        $storageType = ContentStorageType::tryFromSlug($storageTypeSlug) ?? ContentStorageType::DATABASE;
        $editorType = ContentEditorType::tryFromSlug($editorTypeSlug) ?? ContentEditorType::HTML;

        // ページを作成（常にDBにもコンテンツを保存 = バックアップ）
        $page = DixlasePagesPage::create([
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
        // ファイル保存の場合、ファイルからコンテンツを読み込む
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

        // editor_type はモデルの既存値を維持（編集時は変更不可）
        $editorTypeSlug = $page->editor_type->slug();
        $storageTypeSlug = $validated['storage_type'];
        $newStorageType = ContentStorageType::tryFromSlug($storageTypeSlug) ?? ContentStorageType::DATABASE;

        $oldSlug = $page->slug;
        $oldStorageType = $page->storage_type;
        $locale = app()->getLocale();

        // スラッグが変更された場合、ファイルをリネーム
        if ($oldStorageType === ContentStorageType::FILE && $oldSlug !== $validated['slug']) {
            $this->contentService->renameFile($oldSlug, $validated['slug'], $editorTypeSlug, $locale);
            // CSS/JSファイルもリネーム
            $this->contentService->renameAssetFiles($oldSlug, $validated['slug'], $locale);
        }

        $content = $validated['content'] ?? '';
        $customCss = $validated['custom_css'] ?? '';
        $customJs = $validated['custom_js'] ?? '';

        // 保存方法が変更された場合の処理
        if ($oldStorageType !== $newStorageType) {
            if ($oldStorageType === ContentStorageType::FILE && $newStorageType === ContentStorageType::DATABASE) {
                // ファイル→DB: ファイルを削除（DBには常にバックアップがあるため読み込み不要）
                $this->contentService->deleteFile($validated['slug'], $locale, $editorTypeSlug);
                $this->contentService->deleteCssFile($validated['slug'], $locale);
                $this->contentService->deleteJsFile($validated['slug'], $locale);
            }
        }

        // ファイル保存の場合はファイルにも保存
        if ($newStorageType === ContentStorageType::FILE) {
            $this->contentService->saveToFile(
                $validated['slug'],
                $locale,
                $editorTypeSlug,
                $content
            );

            // CSS/JSファイルも保存
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

        // ページを更新（常にDBにもコンテンツを保存 = バックアップ）
        $page->update([
            'slug' => $validated['slug'],
            'title' => $validated['title'] ?? null,
            'storage_type' => $newStorageType,
            'status' => $validated['status'],
            'published_at' => $validated['published_at'] ?? null,
            'content' => $content,
            'custom_css' => $customCss,
            'custom_js' => $customJs,
        ]);

        return redirect()
            ->route('dixlase-pages::admin.pages.edit', $page)
            ->with('success', __('dixlase-pages::admin/pages/edit.success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DixlasePagesPage $page)
    {
        // ファイル保存の場合、関連ファイルも削除
        if ($page->storage_type === ContentStorageType::FILE) {
            $this->contentService->deleteDirectory($page->slug);
        }

        $page->delete();

        return redirect()
            ->route('dixlase-pages::admin.pages.index')
            ->with('success', __('dixlase-pages::admin/pages/index.delete_success'));
    }

    /**
     * Show the settings page.
     */
    public function settings()
    {
        // Determine admin mode (Simple=0, Advanced=1)
        $isSimpleMode = (int) BaseSetting::getValue('admin_mode', 0) === 0;

        // 設定データを取得
        $settings = DixlasePagesPageSetting::pluck('value', 'name')->toArray();

        // デフォルトステータスのラジオカードオプション
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

        // エディタータイプのラジオカードオプション（Bladeは現在無効）
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
            // Bladeエディタは現バージョンでは無効
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

        // ストレージタイプのラジオカードオプション
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

        // ページディレクトリのURL表示用ベースURL
        $siteUrl = config('app.url');

        // 公開権限のロールスライダー用データ
        // 編集権限以上のロールのみ選択可能（ゲスト/受付/寄稿者は除外）
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

        // 現在の publish_min_role 設定値
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
        $validated = $request->validated();

        // 設定をデータベースに保存
        DixlasePagesPageSetting::setMany($validated);

        return redirect()
            ->route('dixlase-pages::admin.pages.settings')
            ->with('success', __('dixlase-pages::admin/pages/settings.success'));
    }

    /**
     * Get file content for a specific editor type (API endpoint).
     */
    public function getFileContent(DixlasePagesPage $page, string $editorType)
    {
        // ファイル保存でない場合は空を返す
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
            // ファイルからコンテンツを読み込む
            $content = $this->contentService->loadFromFile(
                $page->slug,
                app()->getLocale(),
                $editorType
            ) ?? '';
        } else {
            // DBから content カラムを読み込む
            $content = $page->content ?? '';
        }

        return response()->json(['content' => $content]);
    }
}
