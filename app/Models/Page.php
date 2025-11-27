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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Plugins\DixlasePages\App\Enums\PageStatus;

class Page extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * テーブル名
     */
    protected $table = 'plg_dixlase_pages';

    /**
     * 一括代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'content',
        'status',
        'published_at',
        'meta_description',
        'ogp_image',
        'ogp_image_id',
    ];

    /**
     * キャストする属性
     *
     * @var array
     */
    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }

    /**
     * ステータスのアクセサー（安全な変換）
     */
    public function getStatusAttribute($value): PageStatus
    {
        // 古いデータの変換
        return match($value) {
            '0', 0, 'draft', null => PageStatus::DRAFT,
            '1', 1, 'published' => PageStatus::PUBLISHED,
            '2', 2, 'scheduled' => PageStatus::SCHEDULED,
            default => PageStatus::DRAFT,
        };
    }

    /**
     * ステータスのミューテーター
     */
    public function setStatusAttribute($value): void
    {
        if ($value instanceof PageStatus) {
            $this->attributes['status'] = $value->value;
        } else {
            // 文字列の場合はそのまま保存
            $this->attributes['status'] = $value;
        }
    }

    /**
     * 公開されているかどうかを判定
     */
    public function isPublished(): bool
    {
        return match($this->status) {
            PageStatus::PUBLISHED => true,
            PageStatus::SCHEDULED => $this->published_at && $this->published_at->isPast(),
            PageStatus::DRAFT => false,
        };
    }

    /**
     * 公開可能なページのスコープ
     */
    public function scopePublished($query)
    {
        return $query->where(function ($q) {
            $q->where('status', PageStatus::PUBLISHED->value)
              ->orWhere(function ($sq) {
                  $sq->where('status', PageStatus::SCHEDULED->value)
                     ->where('published_at', '<=', now());
              });
        });
    }

    /**
     * 下書きページのスコープ
     */
    public function scopeDraft($query)
    {
        return $query->where('status', PageStatus::DRAFT->value);
    }

    /**
     * 日付指定ページのスコープ
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', PageStatus::SCHEDULED->value);
    }

    /**
     * OGP画像とのリレーション
     */
    public function ogpImage()
    {
        return $this->belongsTo(Media::class, 'ogp_image_id');
    }

    /**
     * ファクトリーの場所を指定
     */
    protected static function newFactory()
    {
        return \Plugins\DixlasePages\Database\Factories\PageFactory::new();
    }
}
