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


namespace Plugins\DixlasePages\App\Providers;

use App\Contracts\RouteSlugProvider;
use App\DTO\RouteSlug\RegisteredSlug;
use App\Traits\PluginLoaderTrait;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Plugins\DixlasePages\App\Models\DixlasePagesPageSetting;
use Plugins\DixlasePages\App\Services\DixlasePagesPageLinkableProvider;

class DixlasePagesServiceProvider extends ServiceProvider implements RouteSlugProvider
{
    use PluginLoaderTrait;
    
    public function register()
    {
        // LinkableProviderを登録
        $this->app->singleton(DixlasePagesPageLinkableProvider::class);
        
        // タグ付けして、メニュープラグインから取得できるようにする
        $this->app->tag([DixlasePagesPageLinkableProvider::class], 'linkable.providers');
    }

    public function boot()
    {
        // ルートスラッグプロバイダーの登録
        $this->registerRouteSlugProvider();

        // ビューの登録
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'dixlase-pages');

        // 翻訳ファイルの登録
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'dixlase-pages');

        // マイグレーションの登録
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // 注: ルート（routes/web.php, routes/admin.php, routes/api.php）はPluginServiceProviderが自動読み込み
    }

    /**
     * ルートスラッグプロバイダーを登録
     */
    protected function registerRouteSlugProvider(): void
    {
        if (app()->bound(\App\Services\RouteSlugRegistry::class)) {
            app(\App\Services\RouteSlugRegistry::class)
                ->registerProvider('dixlase-pages', $this);
        }
    }

    /**
     * プラグインが管理するルートスラッグを返す
     *
     * @return array<RegisteredSlug>
     */
    public function getRouteSlugs(): array
    {
        try {
            $slug = DixlasePagesPageSetting::getValue('pages_directory', 'pages');
        } catch (\Exception $e) {
            $slug = 'pages';
        }

        return [
            new RegisteredSlug(
                slug: $slug,
                owner: 'dixlase-pages:directory',
                label: 'dixlase-pages::route-slug.owners.directory',
            ),
        ];
    }
}
