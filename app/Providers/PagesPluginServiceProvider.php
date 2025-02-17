<?php

/**
 * This file is part of MySoftware.
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


namespace Plugins\Pagesplugin\App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use App\Traits\PluginLoaderTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class PagesPluginServiceProvider extends ServiceProvider
{
    use PluginLoaderTrait;

    protected $pluginName = 'PagesPlugin';
    protected $pluginAlias = 'pages-plugin';
    protected $pluginBasePath = 'plugins/';
    protected $customPluginBasePath = 'custom/plugins/';



    public function __construct($app)
    {
        parent::__construct($app);

        // カスタムファイルのディレクトリを追加
        $pluginsDir = base_path(config('plugins.plugins_directory', 'plugins'));
        $customFilesDir = base_path(config('custom.custom_files_dir', 'custom'));

        $this->pluginBasePath = "{$pluginsDir}/{$this->pluginName}";
        $this->customPluginBasePath =  "{$customFilesDir}/{$pluginsDir}/{$this->pluginName}";
    }

    public function register()
    {

        // デフォルトおよびカスタムのコンフィグディレクトリを設定
        $defaultConfigDir = "{$this->pluginBasePath}/config";
        $customConfigDir = "{$this->customPluginBasePath}/config";

        // コンフィグをロードしてnamespace `events-plugin` に登録
        $this->loadPluginConfigs($defaultConfigDir, $customConfigDir, $this->pluginAlias);


        //イベントプラグインのナビゲーションを追加
        // 現在のナビゲーションを取得
        $defaultNav = config('admin.nav', []);

        // `dashboard` の次に挿入
        $position = array_search('dashboard', array_keys($defaultNav)) + 1;

        // プラグインのナビゲーションを取得して挿入
        $pluginNav = config("{$this->pluginAlias}.admin.nav") ?? [];

        $updatedNav = array_slice($defaultNav, 0, $position, true) +
            $pluginNav +
            array_slice($defaultNav, $position, null, true);

        // 設定を更新
        config(['admin.nav' => $updatedNav]);
    }
    public function boot()
    {
        // ビューのネームスペースを追加
        $this->loadPluginViews(
            base_path('plugins/PagesPlugin/resources/views'),
            base_path('custom/plugins/PagesPlugin/resources/views'),
            'pages-plugins'
        );

        /*
        //プラグインのリソースファイルの読み込み
        //ルートのロード
        $this->loadPluginRoutes($this->customPluginBasePath . '/routes', $this->pluginBasePath . '/routes');
        // ビューのロード
        $this->loadPluginViews($this->customPluginBasePath . '/resources/views', $this->pluginBasePath . '/resources/views', $this->pluginAlias);
        // マイグレーションのロード
        $this->loadPluginMigrations($this->customPluginBasePath . '/migrations', $this->pluginBasePath . '/migrations');
        // 言語ファイルのロード
        $this->loadPluginTranslations($this->customPluginBasePath . '/lang', $this->pluginBasePath . '/lang', $this->pluginAlias);
        */
    }

    protected function loadPluginViews($corePath, $customPath, $namespace)
    {
        if (is_dir($customPath)) {
            View::addNamespace($namespace, $customPath);
        }

        if (is_dir($corePath)) {
            View::addNamespace($namespace, $corePath);
        }
    }
}
