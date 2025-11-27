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

namespace Plugins\DixlasePages\App\Models;

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageTranslation extends Model
{
    /**
     * テーブル名
     */
    protected $table = 'plg_dixlase_page_translations';

    /**
     * 一括代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'page_id',
        'locale',
        'title',
        'content',
        'meta_description',
        'ogp_image',
        'ogp_image_id',
    ];

    /**
     * ページとのリレーション
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    /**
     * OGP画像とのリレーション
     */
    public function ogpImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'ogp_image_id');
    }

    /**
     * ogp_image_idの空文字列をnullに変換
     */
    public function setOgpImageIdAttribute($value): void
    {
        $this->attributes['ogp_image_id'] = $value === '' ? null : $value;
    }

    /**
     * 翻訳が完全かチェック
     */
    public function isComplete(): bool
    {
        return !empty($this->title) && !empty($this->content);
    }

    /**
     * 翻訳の完成度を取得（0-100%）
     */
    public function completeness(): int
    {
        $fields = ['title', 'content', 'meta_description'];
        $filledCount = 0;

        foreach ($fields as $field) {
            if (!empty($this->{$field})) {
                $filledCount++;
            }
        }

        return (int) round(($filledCount / count($fields)) * 100);
    }
}
