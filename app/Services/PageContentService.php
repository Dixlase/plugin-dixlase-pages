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

use App\Services\ContentFileService;
use Plugins\DixlasePages\App\Models\Page;

/**
 * ページコンテンツサービス
 * コアのContentFileServiceを継承し、ページ固有の機能を追加
 */
class PageContentService extends ContentFileService
{
    /**
     * プラグインスラッグ
     */
    protected const PLUGIN_SLUG = 'dixlase-pages';

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // storage/app/private/plugins/dixlase-pages/{page-slug}/
        parent::__construct('plugins/' . self::PLUGIN_SLUG, 'local', 'en');
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
        $storageType = $page->storage_type->value ?? 'database';
        $editorType = $page->editor_type->value ?? 'html';

        if ($storageType === 'file') {
            return $this->loadFromFile($page->slug, $locale, $editorType);
        }

        // データベースから取得（エディタータイプ別カラム）
        $translation = $page->translate($locale);
        if ($translation) {
            $contentColumn = 'content_' . $editorType;
            return $translation->{$contentColumn} ?? $translation->content ?? null;
        }
        
        return null;
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
        $storageType = $page->storage_type->value ?? 'database';
        $editorType = $page->editor_type->value ?? 'html';

        if ($storageType === 'file') {
            return $this->saveToFile($page->slug, $locale, $editorType, $content);
        }

        // データベースに保存（翻訳テーブルへの保存はコントローラーで行う）
        return true;
    }
}
