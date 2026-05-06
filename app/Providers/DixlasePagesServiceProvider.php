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

namespace Plugins\DixlasePages\App\Providers;

use App\Contracts\RouteSlugProvider;
use App\DTO\RouteSlug\RegisteredSlug;
use App\Traits\PluginLoaderTrait;
use Illuminate\Support\ServiceProvider;
use Plugins\DixlasePages\App\Models\DixlasePagesPageSetting;
use Plugins\DixlasePages\App\Services\DixlasePagesLocalizedUrlProvider;
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

        // Phase D: tag the LocalizedUrlProvider implementation so the
        // multilingual plugin's LocalizedUrlAggregator can collect it
        // when emitting alternate-language URLs (hreflang, sitemap, etc.).
        // Optional dependency: nothing breaks if multilingual is absent.
        $this->app->singleton(DixlasePagesLocalizedUrlProvider::class);
        $this->app->tag([DixlasePagesLocalizedUrlProvider::class], 'localized-url.providers');
    }

    public function boot()
    {
        // ルートスラッグプロバイダーの登録
        $this->registerRouteSlugProvider();

        // ビューの登録（テーマによる上書きを優先）
        $this->registerPluginViews();

        // 翻訳ファイルの登録
        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'dixlase-pages');

        // マイグレーションの登録
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // 注: ルート（routes/web.php, routes/admin.php, routes/api.php）はPluginServiceProviderが自動読み込み
    }

    /**
     * プラグインビューを登録（custom / テーマによる上書きをサポート）
     *
     * 検索優先順位:
     * 1. /custom/plugins/DixlasePages/resources/views/ — サイト固有カスタマイズ
     * 2. themes/{有効テーマ}/plugins/DixlasePages/resources/views/ — テーマによる上書き
     * 3. plugins/DixlasePages/resources/views/ — プラグインのデフォルト
     */
    protected function registerPluginViews(): void
    {
        $namespace = 'dixlase-pages';
        $pluginRelativePath = 'plugins/DixlasePages/resources/views';

        // 1. /custom/ からの上書き（最優先）
        $customPath = base_path("custom/{$pluginRelativePath}");
        if (is_dir($customPath)) {
            $this->app['view']->addNamespace($namespace, $customPath);
        }

        // 2. 有効テーマからの上書き
        $themePath = $this->getThemeOverridePath($pluginRelativePath);
        if ($themePath && is_dir($themePath)) {
            $this->app['view']->addNamespace($namespace, $themePath);
        }

        // 3. プラグインのデフォルトビュー
        $this->loadViewsFrom(__DIR__.'/../../resources/views', $namespace);
    }

    /**
     * テーマのプラグインビュー上書きパスを取得
     */
    protected function getThemeOverridePath(string $pluginRelativePath): ?string
    {
        $themeDirectory = config('themes.theme_directory', 'themes');

        try {
            if (app()->bound(\App\Contracts\Repositories\ThemeRepositoryInterface::class)) {
                $themeRepo = app(\App\Contracts\Repositories\ThemeRepositoryInterface::class);
                $enabledTheme = $themeRepo->getEnabledThemeDirectory();

                return base_path("{$themeDirectory}/{$enabledTheme}/{$pluginRelativePath}");
            }
        } catch (\Exception $e) {
            // テーマ未設定時は無視
        }

        return null;
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
            $slug = DixlasePagesPageSetting::getValue('route_slug', 'pages');
        } catch (\Exception $e) {
            $slug = 'pages';
        }

        return [
            new RegisteredSlug(
                slug: $slug,
                owner: 'dixlase-pages:route_slug',
                label: 'dixlase-pages::route-slug.owners.route_slug',
            ),
        ];
    }
}
