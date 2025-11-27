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

namespace Plugins\DixlasePages\App\Services;

use Illuminate\Support\Facades\Storage;
use Plugins\DixlasePages\App\Models\Page;

class PageContentService
{
    /**
     * ファイル保存用のディスク
     */
    protected string $disk = 'local';

    /**
     * ファイル保存用のベースパス
     */
    protected string $basePath = 'pages';

    /**
     * エディタータイプに対応するファイル拡張子
     */
    protected array $extensions = [
        'markdown' => 'md',
        'html' => 'html',
        'blade' => 'blade.php',
    ];

    /**
     * ファイルからコンテンツを読み込む
     *
     * @param string $slug ページのスラッグ
     * @param string $locale 言語コード
     * @param string $editorType エディタータイプ
     * @return string|null ファイルの内容、存在しない場合はnull
     */
    public function loadFromFile(string $slug, string $locale, string $editorType): ?string
    {
        $filePath = $this->getFilePath($slug, $locale, $editorType);

        if (Storage::disk($this->disk)->exists($filePath)) {
            return Storage::disk($this->disk)->get($filePath);
        }

        return null;
    }

    /**
     * コンテンツをファイルに保存する
     *
     * @param string $slug ページのスラッグ
     * @param string $locale 言語コード
     * @param string $editorType エディタータイプ
     * @param string $content コンテンツ
     * @return bool 保存成功時はtrue
     */
    public function saveToFile(string $slug, string $locale, string $editorType, string $content): bool
    {
        $filePath = $this->getFilePath($slug, $locale, $editorType);

        // ディレクトリが存在しない場合は作成
        $directory = dirname($filePath);
        if (!Storage::disk($this->disk)->exists($directory)) {
            Storage::disk($this->disk)->makeDirectory($directory);
        }

        return Storage::disk($this->disk)->put($filePath, $content);
    }

    /**
     * ファイルを削除する
     *
     * @param string $slug ページのスラッグ
     * @param string $locale 言語コード
     * @param string $editorType エディタータイプ
     * @return bool 削除成功時はtrue
     */
    public function deleteFile(string $slug, string $locale, string $editorType): bool
    {
        $filePath = $this->getFilePath($slug, $locale, $editorType);

        if (Storage::disk($this->disk)->exists($filePath)) {
            return Storage::disk($this->disk)->delete($filePath);
        }

        return true;
    }

    /**
     * ページの全言語のファイルを削除する
     *
     * @param string $slug ページのスラッグ
     * @param string $editorType エディタータイプ
     * @param array $locales 言語コードの配列
     * @return bool すべて削除成功時はtrue
     */
    public function deleteAllFiles(string $slug, string $editorType, array $locales): bool
    {
        $success = true;
        foreach ($locales as $locale) {
            if (!$this->deleteFile($slug, $locale, $editorType)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * スラッグ変更時にファイルをリネームする
     *
     * @param string $oldSlug 旧スラッグ
     * @param string $newSlug 新スラッグ
     * @param string $editorType エディタータイプ
     * @param array $locales 言語コードの配列
     * @return bool すべてリネーム成功時はtrue
     */
    public function renameFiles(string $oldSlug, string $newSlug, string $editorType, array $locales): bool
    {
        $success = true;
        foreach ($locales as $locale) {
            $oldPath = $this->getFilePath($oldSlug, $locale, $editorType);
            $newPath = $this->getFilePath($newSlug, $locale, $editorType);

            if (Storage::disk($this->disk)->exists($oldPath)) {
                if (!Storage::disk($this->disk)->move($oldPath, $newPath)) {
                    $success = false;
                }
            }
        }
        return $success;
    }

    /**
     * ファイルパスを取得する
     * 英語(en)はデフォルトファイル名（言語コードなし）、他言語は言語コード付き
     *
     * @param string $slug ページのスラッグ
     * @param string $locale 言語コード
     * @param string $editorType エディタータイプ
     * @return string ファイルパス
     */
    public function getFilePath(string $slug, string $locale, string $editorType): string
    {
        $extension = $this->extensions[$editorType] ?? 'txt';
        
        // 英語(en)はデフォルトファイル名、他言語は言語コード付き
        if ($locale === 'en') {
            return "{$this->basePath}/{$slug}.{$extension}";
        }
        
        return "{$this->basePath}/{$slug}.{$locale}.{$extension}";
    }

    /**
     * ファイルの絶対パスを取得する
     *
     * @param string $slug ページのスラッグ
     * @param string $locale 言語コード
     * @param string $editorType エディタータイプ
     * @return string 絶対ファイルパス
     */
    public function getAbsoluteFilePath(string $slug, string $locale, string $editorType): string
    {
        $relativePath = $this->getFilePath($slug, $locale, $editorType);
        return Storage::disk($this->disk)->path($relativePath);
    }

    /**
     * ページのコンテンツを取得する（DB or ファイル）
     *
     * @param Page $page ページモデル
     * @param string $locale 言語コード
     * @return string|null コンテンツ
     */
    public function getContent(Page $page, string $locale): ?string
    {
        $storageType = $page->storage_type ?? 'database';
        $editorType = $page->editor_type ?? 'html';

        if ($storageType === 'file') {
            return $this->loadFromFile($page->slug, $locale, $editorType);
        }

        // データベースから取得
        $translation = $page->translate($locale);
        return $translation?->content;
    }

    /**
     * ページのコンテンツを保存する（DB or ファイル）
     *
     * @param Page $page ページモデル
     * @param string $locale 言語コード
     * @param string $content コンテンツ
     * @return bool 保存成功時はtrue
     */
    public function saveContent(Page $page, string $locale, string $content): bool
    {
        $storageType = $page->storage_type ?? 'database';
        $editorType = $page->editor_type ?? 'html';

        if ($storageType === 'file') {
            return $this->saveToFile($page->slug, $locale, $editorType, $content);
        }

        // データベースに保存（翻訳テーブルへの保存はコントローラーで行う）
        return true;
    }

    /**
     * ファイルが存在するか確認する
     *
     * @param string $slug ページのスラッグ
     * @param string $locale 言語コード
     * @param string $editorType エディタータイプ
     * @return bool ファイルが存在する場合はtrue
     */
    public function fileExists(string $slug, string $locale, string $editorType): bool
    {
        $filePath = $this->getFilePath($slug, $locale, $editorType);
        return Storage::disk($this->disk)->exists($filePath);
    }
}
