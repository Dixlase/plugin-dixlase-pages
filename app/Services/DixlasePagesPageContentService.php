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

namespace Plugins\DixlasePages\App\Services;

use App\Enums\ContentStorageType;
use App\Traits\ManagesContentFiles;
use Illuminate\Support\Facades\Storage;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;

/**
 * ページコンテンツファイル管理サービス
 * ファイルベースのコンテンツ保存を管理
 * ManagesContentFilesトレイトを使用して共通機能を提供（ライセンス伝搬を避けるため継承なし）
 *
 * ファイル構造: storage/app/private/{plugin-slug}/{page-slug}.{extension}
 * プラグインスラッグはplugin.jsonから動的に取得
 */
class DixlasePagesPageContentService
{
    use ManagesContentFiles;

    /**
     * コンストラクタ
     * plugin.jsonからプラグインスラッグを取得してベースパスを設定
     */
    public function __construct()
    {
        $pluginJson = json_decode(
            file_get_contents(__DIR__.'/../../plugin.json'),
            true
        );
        $pluginSlug = $pluginJson['slug'] ?? 'dixlase-pages';

        // storage/app/private/{plugin-slug}/{page-slug}.{extension}
        $this->basePath = $pluginSlug;
        $this->disk = 'local';
        $this->defaultLocale = 'en';
    }

    /**
     * ファイルパスを取得する（フラット構造）
     * 構造: {basePath}/{slug}.{extension} または {basePath}/{slug}.{locale}.{extension}
     * デフォルト言語はファイル名に言語コードを付けない
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @param  string  $editorType  エディタータイプ
     * @return string ファイルパス
     */
    public function getFilePath(string $slug, string $locale, string $editorType): string
    {
        $extension = $this->extensions[$editorType] ?? 'txt';

        // デフォルト言語はファイル名に言語コードを付けない
        if ($locale === $this->defaultLocale) {
            return "{$this->basePath}/{$slug}.{$extension}";
        }

        return "{$this->basePath}/{$slug}.{$locale}.{$extension}";
    }

    /**
     * スラッグ変更時にファイルをリネームする（単一ロケール対応）
     *
     * @param  string  $oldSlug  旧スラッグ
     * @param  string  $newSlug  新スラッグ
     * @param  string  $editorType  エディタータイプ
     * @param  string  $locale  言語コード
     * @return bool リネーム成功時はtrue
     */
    public function renameFile(string $oldSlug, string $newSlug, string $editorType, string $locale): bool
    {
        $oldPath = $this->getFilePath($oldSlug, $locale, $editorType);
        $newPath = $this->getFilePath($newSlug, $locale, $editorType);

        if (Storage::disk($this->disk)->exists($oldPath)) {
            return Storage::disk($this->disk)->move($oldPath, $newPath);
        }

        return true;
    }

    /**
     * スラッグに関連するすべてのファイルを削除する（フラット構造対応）
     * ディレクトリではなく、スラッグにマッチするファイルを検索して削除
     *
     * @param  string  $slug  スラッグ
     * @return bool すべて削除成功時はtrue
     */
    public function deleteDirectory(string $slug): bool
    {
        $files = Storage::disk($this->disk)->files($this->basePath);
        $success = true;
        $pattern = '/^'.preg_quote($slug, '/').'\./';

        foreach ($files as $file) {
            $filename = basename($file);
            // {slug}.{ext} または {slug}.{locale}.{ext} パターンに一致するファイルを削除
            if (preg_match($pattern, $filename)) {
                if (! Storage::disk($this->disk)->delete($file)) {
                    $success = false;
                }
            }
        }

        return $success;
    }

    /**
     * JS ファイルパスを取得する
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @return string ファイルパス
     */
    public function getJsFilePath(string $slug, string $locale): string
    {
        if ($locale === $this->defaultLocale) {
            return "{$this->basePath}/{$slug}.js";
        }

        return "{$this->basePath}/{$slug}.{$locale}.js";
    }

    /**
     * CSS ファイルパスを取得する
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @return string ファイルパス
     */
    public function getCssFilePath(string $slug, string $locale): string
    {
        if ($locale === $this->defaultLocale) {
            return "{$this->basePath}/{$slug}.css";
        }

        return "{$this->basePath}/{$slug}.{$locale}.css";
    }

    /**
     * JS コンテンツを取得する（DB or ファイル）
     *
     * @param  DixlasePagesPage  $page  ページモデル
     * @param  string|null  $locale  言語コード（nullの場合は現在の言語）
     * @return string|null コンテンツ
     */
    public function getJsContent(DixlasePagesPage $page, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        if ($page->storage_type === ContentStorageType::FILE) {
            $filePath = $this->getJsFilePath($page->slug, $locale);

            if (Storage::disk($this->disk)->exists($filePath)) {
                return Storage::disk($this->disk)->get($filePath);
            }
        }

        return $page->custom_js ?? null;
    }

    /**
     * CSS コンテンツを取得する（DB or ファイル）
     *
     * @param  DixlasePagesPage  $page  ページモデル
     * @param  string|null  $locale  言語コード（nullの場合は現在の言語）
     * @return string|null コンテンツ
     */
    public function getCssContent(DixlasePagesPage $page, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        if ($page->storage_type === ContentStorageType::FILE) {
            $filePath = $this->getCssFilePath($page->slug, $locale);

            if (Storage::disk($this->disk)->exists($filePath)) {
                return Storage::disk($this->disk)->get($filePath);
            }
        }

        return $page->custom_css ?? null;
    }

    /**
     * JS コンテンツをファイルに保存する
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @param  string  $content  コンテンツ
     * @return bool 保存成功時はtrue
     */
    public function saveJsToFile(string $slug, string $locale, string $content): bool
    {
        $filePath = $this->getJsFilePath($slug, $locale);

        return Storage::disk($this->disk)->put($filePath, $content);
    }

    /**
     * CSS コンテンツをファイルに保存する
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @param  string  $content  コンテンツ
     * @return bool 保存成功時はtrue
     */
    public function saveCssToFile(string $slug, string $locale, string $content): bool
    {
        $filePath = $this->getCssFilePath($slug, $locale);

        return Storage::disk($this->disk)->put($filePath, $content);
    }

    /**
     * JS ファイルを削除する
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @return bool 削除成功時はtrue
     */
    public function deleteJsFile(string $slug, string $locale): bool
    {
        $filePath = $this->getJsFilePath($slug, $locale);

        if (Storage::disk($this->disk)->exists($filePath)) {
            return Storage::disk($this->disk)->delete($filePath);
        }

        return true;
    }

    /**
     * CSS ファイルを削除する
     *
     * @param  string  $slug  スラッグ
     * @param  string  $locale  言語コード
     * @return bool 削除成功時はtrue
     */
    public function deleteCssFile(string $slug, string $locale): bool
    {
        $filePath = $this->getCssFilePath($slug, $locale);

        if (Storage::disk($this->disk)->exists($filePath)) {
            return Storage::disk($this->disk)->delete($filePath);
        }

        return true;
    }

    /**
     * スラッグ変更時にCSS/JSファイルをリネームする
     *
     * @param  string  $oldSlug  旧スラッグ
     * @param  string  $newSlug  新スラッグ
     * @param  string  $locale  言語コード
     * @return bool リネーム成功時はtrue
     */
    public function renameAssetFiles(string $oldSlug, string $newSlug, string $locale): bool
    {
        $success = true;

        // JS ファイルのリネーム
        $oldJsPath = $this->getJsFilePath($oldSlug, $locale);
        $newJsPath = $this->getJsFilePath($newSlug, $locale);
        if (Storage::disk($this->disk)->exists($oldJsPath)) {
            if (! Storage::disk($this->disk)->move($oldJsPath, $newJsPath)) {
                $success = false;
            }
        }

        // CSS ファイルのリネーム
        $oldCssPath = $this->getCssFilePath($oldSlug, $locale);
        $newCssPath = $this->getCssFilePath($newSlug, $locale);
        if (Storage::disk($this->disk)->exists($oldCssPath)) {
            if (! Storage::disk($this->disk)->move($oldCssPath, $newCssPath)) {
                $success = false;
            }
        }

        return $success;
    }
}
