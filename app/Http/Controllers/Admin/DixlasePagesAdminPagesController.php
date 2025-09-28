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
use Plugins\DixlasePages\App\Http\Requests\Admin\StorePageRequest;
use Plugins\DixlasePages\App\Http\Requests\Admin\UpdatePageRequest;
use Plugins\DixlasePages\App\Enums\PageStatus;
use Illuminate\Http\Request;

class DixlasePagesAdminPagesController extends Controller
{
    use AuthorizesRequests;
    use AdminInterfaceTrait;
    use AdminLoggedInTrait;

    public function __construct()
    {
        $this->initialize();
        $this->initializeAfterLogin();
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Page::query();

        // 検索機能
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // ステータスフィルター
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // ページネーション（件数指定対応）
        $perPage = $request->get('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;
        
        $pages = $query->orderBy('created_at', 'desc')
                      ->paginate($perPage)
                      ->withQueryString();

        return view('dixlase-pages::admin.pages.index', array_merge($this->viewParams, compact('pages')));
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
        $page = Page::create($request->validated());

        return redirect()
            ->route('dixlase-pages::admin.pages.index')
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
        return view('dixlase-pages::admin.pages.edit', array_merge($this->viewParams, compact('page')));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePageRequest $request, Page $page)
    {
        $page->update($request->validated());

        return redirect()
            ->route('dixlase-pages::admin.pages.index')
            ->with('success', 'ページが正常に更新されました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()
            ->route('dixlase-pages::admin.pages.index')
            ->with('success', 'ページが正常に削除されました。');
    }

    /**
     * Show the settings page.
     */
    public function settings()
    {
        // 設定データを取得（将来的にはPagesSettingモデルを作成）
        $settings = [
            'pages_directory' => config('custom.pages_directory', 'pages'),
            'default_status' => 'published',
            'enable_comments' => false,
            'seo_enabled' => true,
        ];
        
        $this->viewParams['settings'] = $settings;
        
        return view('dixlase-pages::admin.settings', $this->viewParams);
    }

    /**
     * Update the settings.
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'pages_directory' => 'required|string|max:255',
            'default_status' => 'required|in:published,draft',
            'enable_comments' => 'boolean',
            'seo_enabled' => 'boolean',
        ]);

        // 将来的にはPagesSettingモデルで保存
        // 現在は一時的にセッションに保存
        session(['pages_settings' => $validated]);

        return redirect()
            ->route('admin.dixlase-pages::admin.pages.settings')
            ->with('success', 'ページ設定が更新されました。');
    }
}
