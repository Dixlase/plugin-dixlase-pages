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

namespace Plugins\DixlasePages\App\Models;

use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DixlasePagesPage extends Model
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
        'slug',
        'title',
        'content',
        'storage_type',
        'editor_type',
        'status',
        'published_at',
    ];

    /**
     * キャストする属性
     *
     * @var array
     */
    protected $casts = [
        'published_at' => 'datetime',
        'storage_type' => ContentStorageType::class,
        'editor_type' => ContentEditorType::class,
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
    public function getStatusAttribute($value): ContentStatus
    {
        // 古いデータの変換
        return match($value) {
            '0', 0, 'draft', null => ContentStatus::DRAFT,
            '1', 1, 'published' => ContentStatus::PUBLISHED,
            '2', 2, 'scheduled' => ContentStatus::SCHEDULED,
            default => ContentStatus::DRAFT,
        };
    }

    /**
     * ステータスのミューテーター
     */
    public function setStatusAttribute($value): void
    {
        if ($value instanceof ContentStatus) {
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
            ContentStatus::PUBLISHED => true,
            ContentStatus::SCHEDULED => $this->published_at && $this->published_at->isPast(),
            ContentStatus::DRAFT => false,
        };
    }

    /**
     * 公開可能なページのスコープ
     */
    public function scopePublished($query)
    {
        return $query->where(function ($q) {
            $q->where('status', ContentStatus::PUBLISHED->value)
              ->orWhere(function ($sq) {
                  $sq->where('status', ContentStatus::SCHEDULED->value)
                     ->where('published_at', '<=', now());
              });
        });
    }

    /**
     * 下書きページのスコープ
     */
    public function scopeDraft($query)
    {
        return $query->where('status', ContentStatus::DRAFT->value);
    }

    /**
     * 日付指定ページのスコープ
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', ContentStatus::SCHEDULED->value);
    }

    /**
     * コンテンツを取得（エディタータイプに応じて）
     * ファイル保存の場合はファイルからコンテンツを読み込む
     * DB保存の場合は content カラムから読み込む
     */
    public function getContentByEditorType(): ?string
    {
        // ファイル保存の場合
        if ($this->storage_type && $this->storage_type->value === 'file') {
            $contentService = app(\Plugins\DixlasePages\App\Services\DixlasePagesPageContentService::class);

            return $contentService->loadFromFile(
                $this->slug,
                app()->getLocale(),
                $this->editor_type->value ?? 'html'
            );
        }

        // DB保存の場合は content カラムから読み込む
        return $this->content;
    }

    /**
     * ページURLのアクセサー
     */
    public function getPageUrlAttribute(): string
    {
        $pagesDirectory = DixlasePagesPageSetting::getValue('url_directory', 'pages');
        return url($pagesDirectory . '/' . $this->slug);
    }

    /**
     * ファクトリーの場所を指定
     */
    protected static function newFactory()
    {
        return \Plugins\DixlasePages\Database\Factories\DixlasePagesPageFactory::new();
    }
}
