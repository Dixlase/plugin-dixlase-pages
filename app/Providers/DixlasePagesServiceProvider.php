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
use Plugins\DixlasePages\App\Services\DixlasePagesPageLinkableProvider;

class DixlasePagesServiceProvider extends ServiceProvider implements RouteSlugProvider
{
    use PluginLoaderTrait;

    public function register()
    {
        // Register LinkableProvider
        $this->app->singleton(DixlasePagesPageLinkableProvider::class);

        // Tag it so it can be retrieved from the menu plugin
        $this->app->tag([DixlasePagesPageLinkableProvider::class], 'linkable.providers');
    }

    public function boot()
    {
        // Register route slug provider
        $this->registerRouteSlugProvider();

        // Register views (prioritize theme overrides)
        $this->registerPluginViews();

        // Register translation files
        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'dixlase-pages');

        // Register migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Note: Routes (routes/web.php, routes/admin.php, routes/api.php) are auto-loaded by PluginServiceProvider
    }

    /**
     * Register plugin views (supports custom / theme overrides)
     *
     * Search priority:
     * 1. /custom/plugins/DixlasePages/resources/views/ — Site-specific customization
     * 2. themes/{active theme}/plugins/DixlasePages/resources/views/ — Theme overrides
     * 3. plugins/DixlasePages/resources/views/ — Plugin defaults
     */
    protected function registerPluginViews(): void
    {
        $namespace = 'dixlase-pages';
        $pluginRelativePath = 'plugins/DixlasePages/resources/views';

        // 1. Overrides from /custom/ (highest priority)
        $customPath = base_path("custom/{$pluginRelativePath}");
        if (is_dir($customPath)) {
            $this->app['view']->addNamespace($namespace, $customPath);
        }

        // 2. Overrides from active theme
        $themePath = $this->getThemeOverridePath($pluginRelativePath);
        if ($themePath && is_dir($themePath)) {
            $this->app['view']->addNamespace($namespace, $themePath);
        }

        // 3. Plugin default views
        $this->loadViewsFrom(__DIR__.'/../../resources/views', $namespace);
    }

    /**
     * Get theme plugin view override path
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
            // Ignore if theme is not set
        }

        return null;
    }

    /**
     * Register route slug provider
     */
    protected function registerRouteSlugProvider(): void
    {
        if (app()->bound(\App\Services\RouteSlugRegistry::class)) {
            app(\App\Services\RouteSlugRegistry::class)
                ->registerProvider('dixlase-pages', $this);
        }
    }

    /**
     * Return route slugs managed by the plugin
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
