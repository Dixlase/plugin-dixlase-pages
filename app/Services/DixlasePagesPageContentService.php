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

use App\Traits\ManagesContentFiles;
use Illuminate\Support\Facades\Storage;

/**
 * ページコンテンツファイル管理サービス
 * ファイルベースのコンテンツ保存を管理
 * ManagesContentFilesトレイトを使用して共通機能を提供（ライセンス伝搬を避けるため継承なし）
 */
class DixlasePagesPageContentService
{
    use ManagesContentFiles;

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
        $this->basePath = 'plugins/' . self::PLUGIN_SLUG;
        $this->disk = 'local';
        $this->defaultLocale = 'en';
    }

    /**
     * スラッグ変更時にファイルをリネームする（単一ロケール対応）
     * コントローラーとの互換性のため、引数順序が異なるラッパーメソッド
     *
     * @param string $oldSlug 旧スラッグ
     * @param string $newSlug 新スラッグ
     * @param string $editorType エディタータイプ
     * @param string $locale 言語コード
     * @return bool リネーム成功時はtrue
     */
    public function renameFile(string $oldSlug, string $newSlug, string $editorType, string $locale): bool
    {
        $oldPath = $this->getFilePath($oldSlug, $locale, $editorType);
        $newPath = $this->getFilePath($newSlug, $locale, $editorType);

        // ファイルが存在する場合はリネーム
        if (Storage::disk($this->disk)->exists($oldPath)) {
            // 新しいディレクトリが存在しない場合は作成
            $newDirectory = dirname($newPath);
            if (!Storage::disk($this->disk)->exists($newDirectory)) {
                Storage::disk($this->disk)->makeDirectory($newDirectory);
            }
            
            return Storage::disk($this->disk)->move($oldPath, $newPath);
        }
        
        return true;
    }
}
