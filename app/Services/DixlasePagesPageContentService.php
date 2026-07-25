<?php

/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace Plugins\DixlasePages\App\Services;

use App\Enums\ContentStorageType;
use App\Traits\ManagesContentFiles;
use Illuminate\Support\Facades\Storage;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;

/**
 * Page content file management service
 * Manages file-based content storage
 * Uses ManagesContentFiles trait to provide common functionality (no inheritance to avoid license propagation)
 *
 * File structure: storage/app/private/{plugin-slug}/{page-slug}.{extension}
 * Plugin slug is dynamically retrieved from plugin.json
 */
class DixlasePagesPageContentService
{
    use ManagesContentFiles;

    /**
     * Constructor
     * Retrieves plugin slug from plugin.json and sets the base path
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
     * Retrieve file path (flat structure)
     * Structure: {basePath}/{slug}.{extension}
     *
     * Each page row already carries its own `lang`, so the filename does not
     * embed the locale.
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code (kept for trait compatibility, unused)
     * @param  string  $editorType  Editor type
     * @return string File path
     */
    public function getFilePath(string $slug, string $locale, string $editorType): string
    {
        $extension = $this->extensions[$editorType] ?? 'txt';

        return "{$this->basePath}/{$slug}.{$extension}";
    }

    /**
     * Rename file when slug changes (single locale support)
     *
     * @param  string  $oldSlug  Old slug
     * @param  string  $newSlug  New slug
     * @param  string  $editorType  Editor type
     * @param  string  $locale  Language code
     * @return bool True on successful rename
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
     * Delete all files related to a slug (flat structure support)
     * Search and delete files matching the slug, not directories
     *
     * @param  string  $slug  Slug
     * @return bool True if all deletions succeed
     */
    public function deleteDirectory(string $slug): bool
    {
        $files = Storage::disk($this->disk)->files($this->basePath);
        $success = true;
        $pattern = '/^'.preg_quote($slug, '/').'\./';

        foreach ($files as $file) {
            $filename = basename($file);
            // Delete files matching {slug}.{ext} or {slug}.{locale}.{ext} pattern
            if (preg_match($pattern, $filename)) {
                if (! Storage::disk($this->disk)->delete($file)) {
                    $success = false;
                }
            }
        }

        return $success;
    }

    /**
     * Retrieve JS file path
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code (kept for signature compatibility, unused)
     * @return string File path
     */
    public function getJsFilePath(string $slug, string $locale): string
    {
        return "{$this->basePath}/{$slug}.js";
    }

    /**
     * Retrieve CSS file path
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code (kept for signature compatibility, unused)
     * @return string File path
     */
    public function getCssFilePath(string $slug, string $locale): string
    {
        return "{$this->basePath}/{$slug}.css";
    }

    /**
     * Retrieve JS content (DB or file)
     *
     * @param  DixlasePagesPage  $page  Page model
     * @param  string|null  $locale  Language code (current language if null)
     * @return string|null Content
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
     * Retrieve CSS content (DB or file)
     *
     * @param  DixlasePagesPage  $page  Page model
     * @param  string|null  $locale  Language code (current language if null)
     * @return string|null Content
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
     * Save JS content to file
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @param  string  $content  Content
     * @return bool True on successful save
     */
    public function saveJsToFile(string $slug, string $locale, string $content): bool
    {
        $filePath = $this->getJsFilePath($slug, $locale);

        return Storage::disk($this->disk)->put($filePath, $content);
    }

    /**
     * Save CSS content to file
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @param  string  $content  Content
     * @return bool True on successful save
     */
    public function saveCssToFile(string $slug, string $locale, string $content): bool
    {
        $filePath = $this->getCssFilePath($slug, $locale);

        return Storage::disk($this->disk)->put($filePath, $content);
    }

    /**
     * Delete JS file
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @return bool True on successful deletion
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
     * Delete CSS file
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @return bool True on successful deletion
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
     * Rename CSS/JS files when slug changes
     *
     * @param  string  $oldSlug  Old slug
     * @param  string  $newSlug  New slug
     * @param  string  $locale  Language code
     * @return bool True on successful rename
     */
    public function renameAssetFiles(string $oldSlug, string $newSlug, string $locale): bool
    {
        $success = true;

        // Rename JS file
        $oldJsPath = $this->getJsFilePath($oldSlug, $locale);
        $newJsPath = $this->getJsFilePath($newSlug, $locale);
        if (Storage::disk($this->disk)->exists($oldJsPath)) {
            if (! Storage::disk($this->disk)->move($oldJsPath, $newJsPath)) {
                $success = false;
            }
        }

        // Rename CSS file
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
