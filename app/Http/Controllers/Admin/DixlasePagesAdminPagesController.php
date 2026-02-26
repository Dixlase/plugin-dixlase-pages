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
use App\Traits\AdminInterfaceTrait;
use App\Traits\AdminLoggedInTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
        $pagesDirectory = DixlasePagesPageSetting::getValue('pages_directory', config('custom.pages_directory', 'pages'));

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
    private function prepareFormData(DixlasePagesPage $page, ?string $fileContents = null): array
    {
        // コンテンツ取得（新規ページの場合は空、既存ページはファイルまたはDBから）
        $content = $fileContents ?? ($page->exists ? ($page->getContentByEditorType() ?? '') : '');

        // ページディレクトリ設定
        $pagesDirectory = DixlasePagesPageSetting::getValue('pages_directory', config('custom.pages_directory', 'pages'));
        $slugBaseUrl = config('app.url').'/'.$pagesDirectory.'/';

        // ストレージ保存方法オプション（form-select用）
        $storageOptions = [];
        $storageDescriptions = [];
        foreach (ContentStorageType::optionsWithDescription() as $value => $option) {
            $storageOptions[$value] = $option['label'];
            $storageDescriptions[$value] = $option['description'];
        }

        // ステータスオプション（form-select用）
        $statusOptions = [];
        foreach (ContentStatus::optionsWithDescription() as $value => $option) {
            $statusOptions[$value] = $option['label'];
        }

        // エディター翻訳キー（Alpine.js $t()用）
        $editorTranslations = [
            'common.content_editor.gui' => __('common.content_editor.gui'),
            'common.content_editor.gui_description' => __('common.content_editor.gui_description'),
            'common.content_editor.markdown' => __('common.content_editor.markdown'),
            'common.content_editor.markdown_description' => __('common.content_editor.markdown_description'),
            'common.content_editor.html' => __('common.content_editor.html'),
            'common.content_editor.html_description' => __('common.content_editor.html_description'),
        ];

        // エディタータイプ別アイコン・色マップ
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

        // old()込みのステータス値（Alpine.js初期化用）
        $statusValue = old('status', $page->status->value);
        $publishedAtValue = old('published_at', $page->published_at ? $page->published_at->format('Y-m-d\TH:i') : '');

        // ファイル保存時の表示用ベースパス
        $fileStorageBasePath = 'storage/app/private/' . $this->contentService->getBasePath();

        return compact(
            'content',
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
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $page = new DixlasePagesPage();

        // 設定のデフォルト値を適用
        $page->status = DixlasePagesPageSetting::getValue('default_status', 'draft');
        $page->editor_type = DixlasePagesPageSetting::getValue('default_editor_type', 'html');
        $page->storage_type = DixlasePagesPageSetting::getValue('default_storage_type', 'database');

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

        // GUIエディタの場合は強制的にDBに
        $storageType = $validated['storage_type'];
        if ($validated['editor_type'] === 'gui') {
            $storageType = 'database';
        }

        $content = $validated['content'] ?? '';

        // ファイル保存の場合はファイルにも保存
        if ($storageType === 'file') {
            $this->contentService->saveToFile(
                $validated['slug'],
                app()->getLocale(),
                $validated['editor_type'],
                $content
            );
        }

        // ページを作成（常にDBにもコンテンツを保存 = バックアップ）
        $page = DixlasePagesPage::create([
            'slug' => $validated['slug'],
            'title' => $validated['title'] ?? null,
            'storage_type' => $storageType,
            'editor_type' => $validated['editor_type'],
            'status' => $validated['status'],
            'published_at' => $validated['published_at'] ?? null,
            'content' => $content,
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
        if ($page->storage_type->value === 'file') {
            $fileContents = $this->contentService->loadFromFile(
                $page->slug,
                app()->getLocale(),
                $page->editor_type->value
            );
        }

        $formData = $this->prepareFormData($page, $fileContents);

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
        $editorType = $page->editor_type->value;

        // GUIエディタの場合は強制的にDBに
        $storageType = $validated['storage_type'];
        if ($editorType === 'gui') {
            $storageType = 'database';
        }

        $oldSlug = $page->slug;
        $oldStorageType = $page->storage_type->value;
        $locale = app()->getLocale();

        // スラッグが変更された場合、ファイルをリネーム
        if ($oldStorageType === 'file' && $oldSlug !== $validated['slug']) {
            $this->contentService->renameFile($oldSlug, $validated['slug'], $editorType, $locale);
        }

        $content = $validated['content'] ?? '';

        // 保存方法が変更された場合の処理
        if ($oldStorageType !== $storageType) {
            if ($oldStorageType === 'file' && $storageType === 'database') {
                // ファイル→DB: ファイルを削除（DBには常にバックアップがあるため読み込み不要）
                $this->contentService->deleteFile($validated['slug'], $locale, $editorType);
            }
        }

        // ファイル保存の場合はファイルにも保存
        if ($storageType === 'file') {
            $this->contentService->saveToFile(
                $validated['slug'],
                $locale,
                $editorType,
                $content
            );
        }

        // ページを更新（常にDBにもコンテンツを保存 = バックアップ）
        $page->update([
            'slug' => $validated['slug'],
            'title' => $validated['title'] ?? null,
            'storage_type' => $storageType,
            'status' => $validated['status'],
            'published_at' => $validated['published_at'] ?? null,
            'content' => $content,
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
        // ファイル保存の場合、関連ディレクトリも削除
        if ($page->storage_type->value === 'file') {
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
        // 設定データを取得
        $settings = DixlasePagesPageSetting::pluck('value', 'name')->toArray();

        // デフォルトステータスのラジオカードオプション
        $statusIcons = [
            'draft' => 'fas fa-pencil-alt',
            'published' => 'fas fa-eye',
        ];
        $statusCardOptions = [];
        foreach ([ContentStatus::DRAFT, ContentStatus::PUBLISHED] as $status) {
            $statusCardOptions[] = [
                'value' => $status->value,
                'label' => $status->label(),
                'description' => $status->description(),
                'icon' => $statusIcons[$status->value],
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
        $editorTypeCardOptions = [];
        foreach (ContentEditorType::cases() as $type) {
            // Bladeエディタは現バージョンでは無効
            if ($type === ContentEditorType::BLADE) {
                continue;
            }
            $editorTypeCardOptions[] = [
                'value' => $type->value,
                'label' => __($type->translationKey()),
                'description' => __($type->descriptionKey()),
                'icon' => $editorIcons[$type->value] ?? 'fas fa-file',
                'color' => $editorColors[$type->value] ?? 'gray',
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
            $storageTypeCardOptions[] = [
                'value' => $type->value,
                'label' => __($type->translationKey()),
                'description' => __($type->descriptionKey()),
                'icon' => $storageIcons[$type->value],
                'color' => $storageColors[$type->value],
            ];
        }

        // ページディレクトリのURL表示用ベースURL
        $siteUrl = config('app.url');

        $this->viewParams['settings'] = $settings;
        $this->viewParams['statusCardOptions'] = $statusCardOptions;
        $this->viewParams['editorTypeCardOptions'] = $editorTypeCardOptions;
        $this->viewParams['storageTypeCardOptions'] = $storageTypeCardOptions;
        $this->viewParams['siteUrl'] = $siteUrl;

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
        if ($page->storage_type->value !== 'file') {
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
