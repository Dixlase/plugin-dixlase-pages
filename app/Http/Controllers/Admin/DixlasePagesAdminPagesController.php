<?php

/**
 * This file is part of DixlasePages.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Plugins\DixlasePages\App\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Traits\AdminInterfaceTrait;
use App\Traits\AdminLoggedInTrait;
use Plugins\DixlasePages\App\Models\Page;
use Plugins\DixlasePages\App\Models\PageSetting;
use Plugins\DixlasePages\App\Http\Requests\Admin\StorePageRequest;
use Plugins\DixlasePages\App\Http\Requests\Admin\UpdatePageRequest;
use Plugins\DixlasePages\App\Http\Requests\Admin\UpdatePagesSettingsRequest;
use Plugins\DixlasePages\App\Enums\PageStatus;
use Plugins\DixlasePages\App\Services\PageContentService;
use App\Helpers\LocaleHelper;
use Illuminate\Http\Request;

class DixlasePagesAdminPagesController extends Controller
{
    use AuthorizesRequests;
    use AdminInterfaceTrait;
    use AdminLoggedInTrait;

    protected PageContentService $contentService;

    public function __construct(PageContentService $contentService)
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
        $query = Page::query();

        // 検索機能（タイトル、コンテンツ、スラッグ、説明文）
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('meta_description', 'like', "%{$search}%");
            });
        }

        // ステータスフィルター
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // ソート設定を取得
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        
        // 有効なソートフィールドのみ許可
        $allowedSorts = ['title', 'slug', 'status', 'created_at', 'updated_at', 'published_at'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }
        
        // 有効なソート順序のみ許可
        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'desc';
        }

        // ページネーション（件数指定対応）
        $perPage = $request->get('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;
        
        $pages = $query->orderBy($sort, $order)
                      ->paginate($perPage)
                      ->withQueryString();

        // ページディレクトリ設定をデータベースから取得
        $pagesDirectory = PageSetting::getValue('pages_directory', config('custom.pages_directory', 'pages'));
        
        // 各ページにURLを追加
        $pages->getCollection()->transform(function ($page) use ($pagesDirectory) {
            $page->page_url = config('app.url') . '/' . $pagesDirectory . '/' . $page->slug;
            return $page;
        });

        return view('dixlase-pages::admin.pages.index', array_merge($this->viewParams, [
            'pages' => $pages,
            'currentSort' => $sort,
            'currentOrder' => $order,
        ]));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $page = new Page();
        return view('dixlase-pages::admin.pages.create', array_merge($this->viewParams, compact('page')));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePageRequest $request)
    {
        $validated = $request->validated();
        
        // GUIエディタの場合は強制的にDBに
        $storageType = $validated['storage_type'];
        if ($validated['editor_type'] === 'gui') {
            $storageType = 'database';
        }
        
        // ページ本体を作成
        $page = Page::create([
            'slug' => $validated['slug'],
            'storage_type' => $storageType,
            'editor_type' => $validated['editor_type'],
            'status' => $validated['status'],
            'published_at' => $validated['published_at'] ?? null,
        ]);

        // 翻訳データを保存
        \Log::info('store: translations debug', [
            'storageType' => $storageType,
            'editorType' => $validated['editor_type'],
            'translations' => $validated['translations'] ?? 'not set',
        ]);
        
        if (isset($validated['translations'])) {
            foreach ($validated['translations'] as $locale => $data) {
                \Log::info("store: processing locale {$locale}", [
                    'hasContent' => isset($data['content']),
                    'contentLength' => isset($data['content']) ? strlen($data['content']) : 0,
                ]);
                
                // ファイル保存の場合はコンテンツをファイルに保存
                if ($storageType === 'file' && isset($data['content'])) {
                    $this->contentService->saveToFile(
                        $page->slug,
                        $locale,
                        $validated['editor_type'],
                        $data['content'] ?? ''
                    );
                    // DBにはコンテンツを保存しない（ファイルパスの参照のみ）
                    $validated['translations'][$locale]['content'] = null;
                }
            }
            $page->setTranslations($validated['translations']);
        }

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'ページが正常に作成されました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Page $page)
    {
        return view('dixlase-pages::admin.pages.show', array_merge($this->viewParams, compact('page')));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Page $page)
    {
        // ファイル保存の場合、ファイルからコンテンツを読み込む
        $fileContents = [];
        if ($page->storage_type->value === 'file') {
            $locales = LocaleHelper::supportedLocales();
            foreach ($locales as $locale) {
                $fileContent = $this->contentService->loadFromFile(
                    $page->slug,
                    $locale,
                    $page->editor_type->value
                );
                if ($fileContent !== null) {
                    $fileContents[$locale] = $fileContent;
                }
            }
        }
        
        return view('dixlase-pages::admin.pages.edit', array_merge($this->viewParams, compact('page', 'fileContents')));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePageRequest $request, Page $page)
    {
        $validated = $request->validated();
        
        // GUIエディタの場合は強制的にDBに
        $storageType = $validated['storage_type'];
        if ($validated['editor_type'] === 'gui') {
            $storageType = 'database';
        }
        
        $oldSlug = $page->slug;
        $oldStorageType = $page->storage_type->value;
        $oldEditorType = $page->editor_type->value;
        $locales = LocaleHelper::supportedLocales();
        
        // スラッグが変更された場合、ファイルをリネーム
        if ($oldStorageType === 'file' && $oldSlug !== $validated['slug']) {
            $this->contentService->renameFiles($oldSlug, $validated['slug'], $oldEditorType, $locales);
        }
        
        // 保存方法が変更された場合の処理
        if ($oldStorageType !== $storageType) {
            if ($oldStorageType === 'file' && $storageType === 'database') {
                // ファイル→DB: ファイルからコンテンツを読み込んでDBに保存、ファイルを削除
                foreach ($locales as $locale) {
                    $fileContent = $this->contentService->loadFromFile($validated['slug'], $locale, $oldEditorType);
                    if ($fileContent !== null && isset($validated['translations'][$locale])) {
                        $validated['translations'][$locale]['content'] = $fileContent;
                    }
                }
                $this->contentService->deleteAllFiles($validated['slug'], $oldEditorType, $locales);
            }
        }
        
        // ページ本体を更新
        $page->update([
            'slug' => $validated['slug'],
            'storage_type' => $storageType,
            'editor_type' => $validated['editor_type'],
            'status' => $validated['status'],
            'published_at' => $validated['published_at'] ?? null,
        ]);

        // 翻訳データを更新
        if (isset($validated['translations'])) {
            foreach ($validated['translations'] as $locale => $data) {
                // ファイル保存の場合はコンテンツをファイルに保存
                if ($storageType === 'file' && isset($data['content'])) {
                    $this->contentService->saveToFile(
                        $page->slug,
                        $locale,
                        $validated['editor_type'],
                        $data['content'] ?? ''
                    );
                    // DBにはコンテンツを保存しない
                    $validated['translations'][$locale]['content'] = null;
                }
            }
            $page->setTranslations($validated['translations']);
        }

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('success', 'ページが正常に更新されました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $page)
    {
        // ファイル保存の場合、関連ファイルも削除
        if ($page->storage_type->value === 'file') {
            $locales = LocaleHelper::supportedLocales();
            $this->contentService->deleteAllFiles($page->slug, $page->editor_type->value, $locales);
        }
        
        $page->delete();

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'ページが正常に削除されました。');
    }

    /**
     * Show the settings page.
     */
    public function settings()
    {
        // 設定データを取得
        $settings = [
            'pages_directory' => PageSetting::getValue('pages_directory', config('custom.pages_directory', 'pages')),
            'default_status' => PageSetting::getValue('default_status', 'published'),
            'enable_comments' => PageSetting::getValue('enable_comments', false),
            'seo_enabled' => PageSetting::getValue('seo_enabled', true),
        ];
        
        $this->viewParams['settings'] = $settings;
        
        return view('dixlase-pages::admin.pages.settings', $this->viewParams);
    }

    /**
     * Update the settings.
     */
    public function updateSettings(UpdatePagesSettingsRequest $request)
    {
        $validated = $request->validated();

        // 設定をデータベースに保存
        PageSetting::setMany($validated);

        return redirect()
            ->route('admin.pages.settings')
            ->with('success', 'ページ設定が更新されました。');
    }
}
