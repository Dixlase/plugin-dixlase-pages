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

use Illuminate\Routing\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Traits\AdminInterfaceTrait;
use App\Traits\AdminLoggedInTrait;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Models\DixlasePagesPageSetting;
use Plugins\DixlasePages\App\Http\Requests\Admin\DixlasePagesStorePageRequest;
use Plugins\DixlasePages\App\Http\Requests\Admin\DixlasePagesUpdatePageRequest;
use Plugins\DixlasePages\App\Http\Requests\Admin\DixlasePagesUpdatePagesSettingsRequest;
use Plugins\DixlasePages\App\Enums\PageStatus;
use Plugins\DixlasePages\App\Services\DixlasePagesPageContentService;
use Illuminate\Http\Request;

class DixlasePagesAdminPagesController extends Controller
{
    use AuthorizesRequests;
    use AdminInterfaceTrait;
    use AdminLoggedInTrait;

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
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('meta_description', 'like', "%{$search}%");
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
        
        $pages = $pages->orderBy($sort, $order)
                      ->paginate($perPage)
                      ->withQueryString();

        // ページディレクトリ設定をデータベースから取得
        $pagesDirectory = DixlasePagesPageSetting::getValue('pages_directory', config('custom.pages_directory', 'pages'));
        
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
        $page = new DixlasePagesPage();
        return view('dixlase-pages::admin.pages.create', array_merge($this->viewParams, compact('page')));
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
        
        // コンテンツカラムの準備
        $contentData = [
            'content' => null,
            'content_markdown' => null,
            'content_html' => null,
            'content_blade' => null,
        ];
        
        if ($storageType === 'file') {
            // ファイル保存の場合はコンテンツをファイルに保存
            $this->contentService->saveToFile(
                $validated['slug'],
                app()->getLocale(),
                $validated['editor_type'],
                $content
            );
        } else {
            // DB保存の場合はエディタータイプ別のカラムに保存
            $contentColumn = 'content_' . $validated['editor_type'];
            $contentData[$contentColumn] = $content;
        }
        
        // ページを作成
        $page = DixlasePagesPage::create([
            'slug' => $validated['slug'],
            'title' => $validated['title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'ogp_image_id' => $validated['ogp_image_id'] ?? null,
            'storage_type' => $storageType,
            'editor_type' => $validated['editor_type'],
            'status' => $validated['status'],
            'published_at' => $validated['published_at'] ?? null,
            ...$contentData,
        ]);

        return redirect()
            ->route('dixlase-pages::admin.pages.index')
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
        
        return view('dixlase-pages::admin.pages.edit', array_merge($this->viewParams, compact('page', 'fileContents')));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DixlasePagesUpdatePageRequest $request, DixlasePagesPage $page)
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
        $locale = app()->getLocale();
        
        // スラッグが変更された場合、ファイルをリネーム
        if ($oldStorageType === 'file' && $oldSlug !== $validated['slug']) {
            $this->contentService->renameFile($oldSlug, $validated['slug'], $oldEditorType, $locale);
        }
        
        $content = $validated['content'] ?? '';
        
        // コンテンツカラムの準備
        $contentData = [
            'content' => null,
            'content_markdown' => null,
            'content_html' => null,
            'content_blade' => null,
        ];
        
        // 保存方法が変更された場合の処理
        if ($oldStorageType !== $storageType) {
            if ($oldStorageType === 'file' && $storageType === 'database') {
                // ファイル→DB: ファイルからコンテンツを読み込んでDBに保存、ファイルを削除
                $fileContent = $this->contentService->loadFromFile($validated['slug'], $locale, $oldEditorType);
                if ($fileContent !== null) {
                    $content = $fileContent;
                }
                $this->contentService->deleteFile($validated['slug'], $locale, $oldEditorType);
            }
        }
        
        if ($storageType === 'file') {
            // ファイル保存の場合はコンテンツをファイルに保存
            $this->contentService->saveToFile(
                $validated['slug'],
                $locale,
                $validated['editor_type'],
                $content
            );
        } else {
            // DB保存の場合はエディタータイプ別のカラムに保存
            $contentColumn = 'content_' . $validated['editor_type'];
            $contentData[$contentColumn] = $content;
        }
        
        // ページを更新
        $page->update([
            'slug' => $validated['slug'],
            'title' => $validated['title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'ogp_image_id' => $validated['ogp_image_id'] ?? null,
            'storage_type' => $storageType,
            'editor_type' => $validated['editor_type'],
            'status' => $validated['status'],
            'published_at' => $validated['published_at'] ?? null,
            ...$contentData,
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
        
        $this->viewParams['settings'] = $settings;
        
        return view('dixlase-pages::admin.pages.settings', $this->viewParams);
    }

    /**
     * Update the settings.
     */
    public function updateSettings(DixlasePagesUpdatePagesSettingsRequest $request)
    {
        $validated = $request->validated();

        // 設定をデータベースに保存
        DixlasePagesPageSetting::updateOrCreate($validated);

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
            // DBからエディタータイプ別のカラムを読み込む
            $contentColumn = 'content_' . $editorType;
            $content = $page->{$contentColumn} ?? '';
        }

        return response()->json(['content' => $content]);
    }
}
