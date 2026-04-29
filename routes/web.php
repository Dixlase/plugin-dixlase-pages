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

use Illuminate\Support\Facades\Route;
use Plugins\DixlasePages\App\Http\Controllers\Front\DixlasePagesCustomAssetController;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Models\DixlasePagesPageSetting;

// 個別ページ
Route::middleware(['front.ip'])->group(
    function () {
        // データベースから設定を取得、なければデフォルト値を使用
        $pagesDirectory = DixlasePagesPageSetting::getValue('route_slug', 'pages');

        // カスタム CSS/JS アセットルート（ページ表示ルートの前に定義）
        Route::get($pagesDirectory.'/{slug}/custom-style.css', [DixlasePagesCustomAssetController::class, 'style'])
            ->name('dixlase-pages::page.custom-style')
            ->withoutMiddleware([\App\Http\Middleware\ContentSecurityPolicy::class]);

        Route::get($pagesDirectory.'/{slug}/custom-script.js', [DixlasePagesCustomAssetController::class, 'script'])
            ->name('dixlase-pages::page.custom-script')
            ->withoutMiddleware([\App\Http\Middleware\ContentSecurityPolicy::class]);

        // ページ表示ルート
        Route::get($pagesDirectory.'/{slug}', function ($slug) {
            $locale = app()->getLocale();

            // 管理画面にログインしている場合は全てのページを表示（プレビュー機能）
            if (auth('member')->check()) {
                $page = DixlasePagesPage::where('slug', $slug)
                    ->forLang($locale)
                    ->firstOrFail();
            } else {
                // ログインしていない場合は公開済みページのみ
                // (published または scheduled で公開日時が過去のもの)
                $page = DixlasePagesPage::where('slug', $slug)
                    ->forLang($locale)
                    ->published()
                    ->firstOrFail();
            }

            // フロントビュー変数を準備
            $contentService = app(\Plugins\DixlasePages\App\Services\DixlasePagesPageContentService::class);
            $editorType = $page->editor_type->slug() ?? 'html';
            $content = $page->getContentByEditorType() ?? '';

            // GUI editor: render JSON content to HTML for front display
            if ($editorType === 'gui' && $content) {
                $editorManager = app(\App\Services\Editor\EditorManager::class);
                $renderedHtml = $editorManager->renderContent('gui', $content);
                if ($renderedHtml !== '') {
                    $content = $renderedHtml;
                    $editorType = 'html';
                }
            }

            $hasCustomCss = ! empty($contentService->getCssContent($page, $locale));
            $hasCustomJs = ! empty($contentService->getJsContent($page, $locale));
            $customAssetVersion = $page->updated_at?->timestamp ?? time();

            return view('dixlase-pages::front.page', compact(
                'page',
                'editorType',
                'content',
                'hasCustomCss',
                'hasCustomJs',
                'customAssetVersion',
            ));
        })->name('dixlase-pages::page.show');
    }
);
