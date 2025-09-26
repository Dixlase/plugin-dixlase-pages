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
use Illuminate\Support\Facades\Config;
use App\Traits\PluginLoaderTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class DixlasePagesServiceProvider extends ServiceProvider
{
    use PluginLoaderTrait;

    public function __construct($app) {}

    public function register()
    {
        // `admin.nav` の設定をマージ
        $this->mergeAdminNavConfig(__DIR__ . '/../../config/admin.php');
    }

    public function boot()
    {
        // ビューの登録
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'pages-plugin');
        
        // 翻訳ファイルの登録
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'pages-plugin');
        
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
                ->prefix(config('security.admin_url', 'admin'))
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
