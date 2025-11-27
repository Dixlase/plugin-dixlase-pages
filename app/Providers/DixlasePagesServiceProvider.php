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
use App\Traits\PluginLoaderTrait;
use Plugins\DixlasePages\App\Services\PageLinkableProvider;

class DixlasePagesServiceProvider extends ServiceProvider
{
    use PluginLoaderTrait;
    
    public function register()
    {
        // Merge admin navigation
        $this->mergeAdminNavigation('DixlasePages', __DIR__ . '/../../config/admin.php');
        
        // LinkableProviderを登録
        $this->app->singleton(PageLinkableProvider::class);
        
        // タグ付けして、メニュープラグインから取得できるようにする
        $this->app->tag([PageLinkableProvider::class], 'linkable.providers');
    }

    public function boot()
    {
        // ビューの登録
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'dixlase-pages');
        
        // 翻訳ファイルの登録
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'dixlase-pages');
        
        // マイグレーションの登録
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        
        // 注: ルート（routes/web.php, routes/admin.php）はPluginServiceProviderが自動読み込み
    }
}
