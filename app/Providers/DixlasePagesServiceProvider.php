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


namespace Plugins\DixlasePages\App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class DixlasePagesServiceProvider extends ServiceProvider
{
    public function register()
    {
        // プラグイン設定の登録
        $this->mergeAdminNavigation();
    }

    /**
     * 管理画面ナビゲーションをマージ
     */
    protected function mergeAdminNavigation()
    {
        $configFile = __DIR__ . '/../../config/admin.php';
        
        if (!file_exists($configFile)) {
            return;
        }

        $pluginConfig = require $configFile;
        
        if (!isset($pluginConfig['nav']) || !is_array($pluginConfig['nav'])) {
            return;
        }

        // 既存のナビゲーション設定を取得
        $existingNav = config('admin.nav', []);
        
        // プラグインのナビゲーション設定をマージ
        foreach ($pluginConfig['nav'] as $key => $value) {
            // _insert_after や _insert_before は無視して直接追加
            unset($value['_insert_after'], $value['_insert_before']);
            $existingNav[$key] = $value;
        }
        
        // 設定を更新
        config(['admin.nav' => $existingNav]);
    }

    public function boot()
    {
        // ビューの登録
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'dixlase-pages');
        
        // 翻訳ファイルの登録
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'dixlase-pages');
        
        // マイグレーションの登録
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        
        // ルートの登録
        $this->loadRoutes();
    }

    /**
     * ルートを読み込む
     */
    protected function loadRoutes()
    {
        // 管理画面ルート
        if (file_exists(__DIR__ . '/../../routes/admin.php')) {
            Route::middleware(['web', 'auth:member', 'admin.ip'])
                ->prefix('admin')
                ->name('admin.')
                ->group(__DIR__ . '/../../routes/admin.php');
        }

        // フロントエンドルート
        if (file_exists(__DIR__ . '/../../routes/web.php')) {
            Route::middleware(['web'])
                ->group(__DIR__ . '/../../routes/web.php');
        }
    }
}
