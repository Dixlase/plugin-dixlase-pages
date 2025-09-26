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

use App\Http\Controllers\Controller;
use Plugins\DixlasePages\App\Models\Page;
use Plugins\DixlasePages\App\Http\Requests\Admin\StorePageRequest;
use Plugins\DixlasePages\App\Http\Requests\Admin\UpdatePageRequest;
use Plugins\DixlasePages\App\Enums\PageStatus;
use Illuminate\Http\Request;

class DixlasePagesAdminPagesController extends Controller
{
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

        // ページネーション
        $pages = $query->orderBy('created_at', 'desc')
                      ->paginate(10)
                      ->withQueryString();

        return view('pages-plugin::admin.pages.index', compact('pages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $page = new Page();
        return view('pages-plugin::admin.pages.create', compact('page'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePageRequest $request)
    {
        $page = Page::create($request->validated());

        return redirect()
            ->route('pages-plugin::admin.pages.index')
            ->with('success', 'ページが正常に作成されました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Page $page)
    {
        return view('pages-plugin::admin.pages.show', compact('page'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Page $page)
    {
        return view('pages-plugin::admin.pages.edit', compact('page'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePageRequest $request, Page $page)
    {
        $page->update($request->validated());

        return redirect()
            ->route('pages-plugin::admin.pages.index')
            ->with('success', 'ページが正常に更新されました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()
            ->route('pages-plugin::admin.pages.index')
            ->with('success', 'ページが正常に削除されました。');
    }
}
