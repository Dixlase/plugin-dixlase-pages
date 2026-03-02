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


use Illuminate\Support\Facades\Route;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Models\DixlasePagesPageSetting;

//個別ページ
Route::middleware(['front.ip'])->group(
    function () {
        // データベースから設定を取得、なければデフォルト値を使用
        $pagesDirectory = DixlasePagesPageSetting::getValue('route_slug', 'pages');
        
        Route::get($pagesDirectory . '/{slug}', function ($slug) {
            
            // 管理画面にログインしている場合は全てのページを表示（プレビュー機能）
            if (auth('member')->check()) {
                $page = DixlasePagesPage::where('slug', $slug)->firstOrFail();
            } else {
                // ログインしていない場合は公開済みページのみ
                // (published または scheduled で公開日時が過去のもの)
                $page = DixlasePagesPage::where('slug', $slug)
                    ->published()
                    ->firstOrFail();
            }
            
            return view('dixlase-pages::front.page', compact('page'));
        })->name('dixlase-pages::page.show');
    }
);
