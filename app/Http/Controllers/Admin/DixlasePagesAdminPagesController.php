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


namespace Plugins\PagesPlugin\App\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Plugins\PagesPlugin\App\Http\Requests\Admin\StorePageRequest;
use Plugins\PagesPlugin\App\Http\Requests\Admin\UpdatePageRequest;
use Plugins\PagesPlugin\App\Models\Page;
use App\Traits\AdminInterfaceTrait;
use App\Traits\AdminLoggedInTrait;



class PagesPluginAdminPagesController extends Controller
{

    use AdminInterfaceTrait;
    use AdminLoggedInTrait;

    protected $pagesDirectory;

    public function __construct()
    {
        $this->initialize();
        $this->initializeAfterLogin();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $this->viewParams['heading'] = 'pages-plugin::admin.features.pages.index.heading';


        // ページネーションで取得
        $pages = Page::paginate(10); // 1ページあたり10件表示
        $this->viewParams['pages'] = $pages;
        return view(
            'pages-plugin::admin.pages.index',
            $this->viewParams
        );
    }

    public function create()
    {

        $this->viewParams['heading'] = 'pages-plugin::admin.features.pages.create.heading';

        return view(
            'pages-plugin::admin.pages.create',
            $this->viewParams
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Page $page)
    {
        //
    }

    public function edit(Page $page)
    {
        // 見出し
        $this->viewParams['heading'] = 'pages-plugin::admin.features.pages.edit.heading';

        // 内容を取得
        $this->viewParams['page'] = $page;



        // ビューにデータを渡す
        return view(
            'pages-plugin::admin.pages.edit',
            $this->viewParams
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePageRequest $request)
    {
        // バリデーションを通過したデータを取得
        $validated = $request->validated();

        // ページを作成し、作成したページのインスタンスを取得
        $page = Page::create($validated);

        return redirect()->route('pages-plugin::admin.pages.edit', ['page' => $page->id])->with('success', 'Page created successfully!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePageRequest $request, Page $page)
    {
        $validated = $request->validated();

        // ページを更新
        $page->update($validated);

        return redirect()->route('pages-plugin::admin.pages.edit', ['page' => $page->id])->with('success', 'Page updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('pages-plugin::admin.pages.index', $this->viewParams)->with('success', 'Page deleted successfully!');
    }
}
